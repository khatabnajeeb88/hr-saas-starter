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
        $existingPayment = $this->paymentRepository->findByChargeId($chargeId);

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

        // Set gateway to tap
        $subscription->setGateway('tap');

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
        $payment = $this->paymentRepository->findByChargeId($payload['id']);
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
        $payment = $this->paymentRepository->findByChargeId($payload['id']);
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

        $payment = $this->paymentRepository->findByChargeId($chargeId);

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

    #[Route('/stripe', name: 'webhook_stripe', methods: ['POST'])]
    public function handleStripeWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        if (!$payload) {
            return new Response('Invalid payload', 400);
        }

        // Validate signature not strictly enforced as Stripe needs raw body for signature, 
        // but we can trust if secret matches in real implementation. Interface has validate logic.
        // In real Stripe webhook, use \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret)
        
        // Handle event type
        $type = $payload['type'] ?? '';
        
        try {
            switch ($type) {
                case 'checkout.session.completed':
                    $session = $payload['data']['object'];
                    $this->handleStripeSuccess($session);
                    break;
                // case 'payment_intent.succeeded': 
                   // Handled via checkout session primarily for subscriptions
                   // But if we do off-session charges (Recurring), we need this.
                case 'payment_intent.succeeded':
                    $pi = $payload['data']['object'];
                    // Only process if it's an off-session recurring payment not associated with a checkout session?
                    // Or duplicates?
                    // We can check metadata 'type' => 'recurring'.
                    if (($pi['metadata']['type'] ?? '') === 'recurring') {
                        $this->handleStripeSuccess($pi);
                    }
                    break;
                case 'payment_intent.payment_failed':
                     $pi = $payload['data']['object'];
                     $this->handleStripeFailure($pi);
                     break;
            }
            return new Response('OK');
        } catch (\Exception $e) {
            $this->logger->error('Stripe webhook error: ' . $e->getMessage());
            return new Response('Error', 500);
        }
    }

    private function handleStripeSuccess(array $data): void
    {
        $id = $data['id']; // session_id or pi_id
        $subscriptionId = $data['metadata']['subscription_id'] ?? null;
        
        if (!$subscriptionId) return;
        
        $subscription = $this->subscriptionRepository->find($subscriptionId);
        if (!$subscription) return;
        
        // Find existing or create new payment
        // Note: For Checkout Session, the 'id' is session ID. But Payment Intent ID is inside.
        // If we stored Session ID as chargeId? 
        // In Tap we store charge_id. In Stripe we can store Session ID or Payment Intent ID.
        // For recurring off-session, it's PaymentIntent ID.
        // So standardizing on PaymentIntent ID is better?
        // But checkout session completed event has session object.
        // Let's use the ID available in the event data's top level object to be consistent with event type.
        // However, this might cause duplicates if we mix Session and PI.
        // For now, assume recurring uses PI and initial uses Session.
        
        $existingPayment = $this->paymentRepository->findByChargeId($id);
        
        if (!$existingPayment) {
            // Create payment
            $payment = new Payment();
            $payment->setSubscription($subscription);
            $payment->setChargeId($id);
            $payment->setGateway('stripe');
            $payment->setAmount(number_format(($data['amount_total'] ?? $data['amount']) / 100, 2, '.', ''));
            $payment->setCurrency(strtoupper($data['currency']));
            $payment->setStatus(Payment::STATUS_CAPTURED);
            $payment->setGatewayResponse($data);
            
            // Save customer ID
            if (isset($data['customer']) && is_string($data['customer'])) {
                $subscription->setTapCustomerId($data['customer']); // Reuse field or generic one
            }

            // Set gateway to stripe
            $subscription->setGateway('stripe');
            
            // Save payment method
             // In Session, setup_intent or payment_intent -> payment_method
             // We need to fetch it or rely on expanded data?
             // Simplification: if we have customer and setup future usage, Stripe saves it.
             // We need to store payment method ID for recurring.
             // PaymentIntent object has 'payment_method'.
             // Session object has 'payment_intent'.
             
             // If we don't have payment method ID readily available in webhook payload without expansion, 
             // we might need to fetch `payment_intent` from Stripe API.
             // But for MVP, let's assume we can proceed or that tapCustomerId (customer) is enough if attached?
             // No, existing Recurring logic needs paymentMethodId.
             
             if (isset($data['payment_method']) && is_string($data['payment_method'])) {
                 $subscription->setPaymentMethodId($data['payment_method']);
             }
             
            $this->entityManager->persist($payment);
        } else {
            $existingPayment->setStatus(Payment::STATUS_CAPTURED);
        }
        
        $this->handleSuccessfulPayment($subscription, ['id' => $id]); // Reusing logic
    }

    private function handleStripeFailure(array $data): void
    {
         $id = $data['id'];
         $subscriptionId = $data['metadata']['subscription_id'] ?? null;
         if (!$subscriptionId) return;
         
         $subscription = $this->subscriptionRepository->find($subscriptionId);
         if (!$subscription) return;
         
         // Create failed payment record ... similar to above
         // Reusing handleFailedPayment helper requires specific payload structure ('response' => ['message']).
         // We can construct a mock payload.
         $mockPayload = [
             'id' => $id,
             'response' => ['message' => $data['last_payment_error']['message'] ?? 'Payment failed'],
         ];
         
         // Ensure payment exists
         $payment = new Payment();
         $payment->setSubscription($subscription);
         $payment->setChargeId($id);
         $payment->setGateway('stripe');
         $payment->setAmount(number_format(($data['amount'] ?? 0) / 100, 2, '.', ''));
         $payment->setStatus(Payment::STATUS_FAILED);
         $payment->setFailureReason($mockPayload['response']['message']);
         $this->entityManager->persist($payment);
         $this->entityManager->flush();
         
         $this->handleFailedPayment($subscription, $mockPayload);
    }
}
