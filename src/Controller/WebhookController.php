<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Repository\SubscriptionRepository;
use App\Service\SubscriptionManager;
use App\Service\TapPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook')]
class WebhookController extends AbstractController
{
    public function __construct(
        private TapPaymentService $tapPaymentService,
        private SubscriptionRepository $subscriptionRepository,
        private PaymentRepository $paymentRepository,
        private SubscriptionManager $subscriptionManager,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private \App\Service\PaymentNotificationService $notificationService,
        private \App\Service\InvoiceService $invoiceService,
    ) {
    }

    #[Route('/tap', name: 'webhook_tap', methods: ['POST'])]
    public function handleTapWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!$payload) {
            $this->logger->error('Invalid webhook payload');
            return new Response('Invalid payload', 400);
        }

        // Get hashstring from headers
        $receivedHashString = $request->headers->get('hashstring', '');

        // Validate webhook signature
        if (!$this->tapPaymentService->validateWebhookSignature($payload, $receivedHashString)) {
            $this->logger->error('Invalid webhook signature', [
                'charge_id' => $payload['id'] ?? null,
            ]);
            return new Response('Invalid signature', 401);
        }

        $this->logger->info('Tap webhook received', [
            'charge_id' => $payload['id'] ?? null,
            'status' => $payload['status'] ?? null,
        ]);

        try {
            // Process based on object type
            $objectType = $payload['object'] ?? null;

            if ($objectType === 'charge') {
                $this->handleChargeWebhook($payload);
            } elseif ($objectType === 'refund') {
                $this->handleRefundWebhook($payload);
            }

            return new Response('OK', 200);
        } catch (\Exception $e) {
            $this->logger->error('Error processing webhook', [
                'error' => $e->getMessage(),
                'charge_id' => $payload['id'] ?? null,
            ]);

            // Return 200 to prevent Tap from retrying
            return new Response('Error processed', 200);
        }
    }

    private function handleChargeWebhook(array $payload): void
    {
        $chargeId = $payload['id'];
        $status = strtoupper($payload['status']);

        // Get subscription from metadata
        $subscriptionId = $payload['metadata']['subscription_id'] ?? null;

        if (!$subscriptionId) {
            $this->logger->warning('No subscription ID in webhook metadata', [
                'charge_id' => $chargeId,
            ]);
            return;
        }

        $subscription = $this->subscriptionRepository->find($subscriptionId);

        if (!$subscription) {
            $this->logger->error('Subscription not found', [
                'subscription_id' => $subscriptionId,
                'charge_id' => $chargeId,
            ]);
            return;
        }

        // Check if payment already exists
        $existingPayment = $this->paymentRepository->findByTapChargeId($chargeId);

        if ($existingPayment) {
            // Update existing payment
            $existingPayment->setStatus($this->mapTapStatus($status));
            $existingPayment->setGatewayResponse($payload);

            if ($status === 'FAILED' && isset($payload['response']['message'])) {
                $existingPayment->setFailureReason($payload['response']['message']);
            }

            $this->entityManager->flush();
        } else {
            // Create new payment record
            $payment = $this->tapPaymentService->createPaymentFromCharge($subscription, $payload);
        }

        // Handle based on status
        if ($status === 'CAPTURED') {
            $this->handleSuccessfulPayment($subscription, $payload);
        } elseif (in_array($status, ['FAILED', 'DECLINED'])) {
            $this->handleFailedPayment($subscription, $payload);
        }
    }

    private function handleSuccessfulPayment($subscription, array $payload): void
    {
        // Update subscription status
        if ($subscription->getStatus() === 'pending_payment' || $subscription->onTrial()) {
            $subscription->setStatus('active');
        }

        // Update last payment date
        $subscription->setLastPaymentAt(new \DateTimeImmutable());

        // Save customer ID if provided
        if (isset($payload['customer']['id'])) {
            $subscription->setTapCustomerId($payload['customer']['id']);
        }

        // Save card token if provided and save_card is true
        if (isset($payload['card']['id']) && ($payload['save_card'] ?? false)) {
            $subscription->setPaymentMethodId($payload['card']['id']);
        }

        $this->entityManager->flush();

        $this->logger->info('Payment successful, subscription activated', [
            'subscription_id' => $subscription->getId(),
            'charge_id' => $payload['id'],
        ]);

        // Send payment success email
        $payment = $this->paymentRepository->findByTapChargeId($payload['id']);
        if ($payment) {
            $this->notificationService->sendPaymentSuccessEmail($payment);
            
            // Generate invoice
            try {
                $this->invoiceService->createInvoiceForPayment($payment);
            } catch (\Exception $e) {
                $this->logger->error('Failed to create invoice', [
                    'payment_id' => $payment->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function handleFailedPayment($subscription, array $payload): void
    {
        // If subscription was active, mark as past_due
        if ($subscription->getStatus() === 'active') {
            $subscription->setStatus('past_due');
            $this->entityManager->flush();
        }

        $this->logger->warning('Payment failed', [
            'subscription_id' => $subscription->getId(),
            'charge_id' => $payload['id'],
            'reason' => $payload['response']['message'] ?? 'Unknown',
        ]);

        // Send payment failure email
        $payment = $this->paymentRepository->findByTapChargeId($payload['id']);
        if ($payment) {
            $this->notificationService->sendPaymentFailureEmail($payment);
        }
    }

    private function handleRefundWebhook(array $payload): void
    {
        $chargeId = $payload['charge_id'] ?? null;

        if (!$chargeId) {
            return;
        }

        $payment = $this->paymentRepository->findByTapChargeId($chargeId);

        if ($payment) {
            $payment->setStatus(Payment::STATUS_REFUNDED);
            $this->entityManager->flush();

            $this->logger->info('Payment refunded', [
                'payment_id' => $payment->getId(),
                'refund_id' => $payload['id'],
            ]);
        }
    }

    private function mapTapStatus(string $tapStatus): string
    {
        return match ($tapStatus) {
            'CAPTURED' => Payment::STATUS_CAPTURED,
            'FAILED', 'DECLINED' => Payment::STATUS_FAILED,
            'CANCELLED' => Payment::STATUS_CANCELLED,
            'REFUNDED' => Payment::STATUS_REFUNDED,
            default => Payment::STATUS_PENDING,
        };
    }
}
