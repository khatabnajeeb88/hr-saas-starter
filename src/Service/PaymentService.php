<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Subscription;
use App\Payment\Gateway\PaymentGatewayInterface;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(
        private PaymentGatewayFactory $gatewayFactory,
        private EntityManagerInterface $entityManager,
        private PaymentRepository $paymentRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Initiate a payment session (or charge)
     */
    public function initiatePayment(Subscription $subscription, string $redirectUrl, string $webhookUrl, ?string $gatewayName = null): array
    {
        $gatewayName = $gatewayName ?: 'tap'; // Default to Tap or config
        // Verify gateway exists
        $gateway = $this->gatewayFactory->getGateway($gatewayName);

        $options = [
            'redirect_url' => $redirectUrl,
            'webhook_url' => $webhookUrl,
        ];

        // Create charge/session via gateway
        $response = $gateway->createCharge($subscription, $options);

        // We don't necessarily create a Payment entity here if it's a redirect flow (session).
        // For Tap, the existing logic didn't create Payment until webhook or callback?
        // Checking TapPaymentService: createCharge checks response, returns data.
        // It does NOT create Payment entity. Webhook creates it.
        // So we follow specific gateway flow.
        
        return $response;
    }

    /**
     * Create or update payment from webhook/callback based on normalized data?
     * Or specific gateway logic?
     * Ideally, we normalize the response and save generic Payment.
     */
    public function handlePaymentSuccess(Subscription $subscription, string $gatewayName, array $data): Payment
    {
        // This might be called by webhook handler
        $payment = new Payment();
        $payment->setSubscription($subscription);
        $payment->setGateway($gatewayName);
        
        // Map data fields based on gateway (or use Gateway method to normalize?)
        // Ideally PaymentGatewayInterface has `normalizeResponse` or similar.
        // For now, I'll do conditional logic here or let the Gateway return "Payment" object?
        // "Create Payment from Charge" logic varies by gateway structure.
        
        // I'll leave this flexible for now.
        // The WebhookController already does heavy lifting for Tap.
        // For Stripe, we need similar logic.
        
        return $payment;
    }
}
