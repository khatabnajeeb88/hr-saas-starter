<?php

namespace App\Payment\Gateway;

use App\Entity\Subscription;

interface PaymentGatewayInterface
{
    /**
     * Create a charge or payment session.
     *
     * @param Subscription $subscription The subscription to charge
     * @param array $options Additional options (redirect_url, webhook_url, etc.)
     * @return array The gateway response (raw or normalized)
     */
    public function createCharge(Subscription $subscription, array $options): array;

    /**
     * Retrieve charge details.
     *
     * @param string $chargeId
     * @return array
     */
    public function retrieveCharge(string $chargeId): array;

    /**
     * Refund a charge.
     *
     * @param string $chargeId
     * @param float $amount
     * @param string $currency
     * @param string $reason
     * @return array
     */
    public function refundCharge(string $chargeId, float $amount, string $currency, string $reason = ''): array;

    /**
     * Charge a saved payment method for recurring billing.
     * 
     * @param Subscription $subscription
     * @param array $options
     * @return array Response data including status and id
     */
    public function chargeSavedPaymentMethod(Subscription $subscription, array $options): array;


    /**
     * Validate webhook signature.
     *
     * @param array $payload Request payload
     * @param string $signature Signature from headers
     * @return bool
     */
    public function validateWebhookSignature(array $payload, string $signature): bool;

    /**
     * Get the unique name of the gateway.
     */
    public function getName(): string;
}
