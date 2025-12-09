<?php

namespace App\Payment\Gateway;

use App\Entity\Subscription;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TapGateway extends AbstractPaymentGateway
{
    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        private string $secretKey,
        private string $publicKey,
        private string $apiUrl
    ) {
        parent::__construct($httpClient, $logger);
    }

    public function getName(): string
    {
        return 'tap';
    }

    public function createCharge(Subscription $subscription, array $options): array
    {
        $redirectUrl = $options['redirect_url'] ?? '';
        $webhookUrl = $options['webhook_url'] ?? '';
        $savedCardToken = $options['source_id'] ?? null; // For saved cards

        $team = $subscription->getTeam();
        $plan = $subscription->getPlan();
        $owner = $team->getOwner();

        $chargeData = [
            'amount' => (float) $plan->getPrice(),
            'currency' => $plan->getCurrency() ?? 'USD',
            'customer' => [
                'first_name' => explode(' ', $owner->getEmail())[0] ?? 'Customer',
                'email' => $owner->getEmail(),
            ],
            'source' => [
                'id' => $savedCardToken ?? 'src_all',
            ],
            'redirect' => [
                'url' => $redirectUrl,
            ],
            'post' => [
                'url' => $webhookUrl,
            ],
            'description' => sprintf(
                '%s - %s Subscription',
                $plan->getName(),
                ucfirst($plan->getBillingInterval())
            ),
            'metadata' => [
                'subscription_id' => $subscription->getId(),
                'team_id' => $team->getId(),
                'plan_id' => $plan->getId(),
                'gateway' => $this->getName(),
            ],
        ];

        // Add customer ID if exists
        if ($subscription->getTapCustomerId()) {
            $chargeData['customer']['id'] = $subscription->getTapCustomerId();
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/charges', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $chargeData,
            ]);

            $data = $response->toArray();

            $this->logInfo('Charge created', [
                'charge_id' => $data['id'] ?? null,
                'subscription_id' => $subscription->getId(),
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logError('Failed to create charge', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->getId(),
            ]);

            throw new \RuntimeException('Tap: Failed to create payment charge: ' . $e->getMessage());
        }
    }

    public function retrieveCharge(string $chargeId): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl . '/charges/' . $chargeId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logError('Failed to retrieve charge', [
                'error' => $e->getMessage(),
                'charge_id' => $chargeId,
            ]);

            throw new \RuntimeException('Tap: Failed to retrieve charge: ' . $e->getMessage());
        }
    }

    public function refundCharge(string $chargeId, float $amount, string $currency, string $reason = ''): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/refunds', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'charge_id' => $chargeId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'reason' => $reason,
                ],
            ]);

            $data = $response->toArray();

            $this->logInfo('Refund processed', [
                'refund_id' => $data['id'] ?? null,
                'charge_id' => $chargeId,
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logError('Failed to process refund', [
                'error' => $e->getMessage(),
                'charge_id' => $chargeId,
            ]);

            throw new \RuntimeException('Tap: Failed to process refund: ' . $e->getMessage());
        }
    }

    public function chargeSavedPaymentMethod(Subscription $subscription, array $options): array
    {
        $webhookUrl = $options['webhook_url'] ?? '';
        
        $team = $subscription->getTeam();
        $plan = $subscription->getPlan();

        $chargeData = [
            'amount' => (float) $plan->getPrice(),
            'currency' => $plan->getCurrency() ?? 'USD',
            'source' => [
                'id' => $subscription->getPaymentMethodId(),
            ],
            'post' => [
                'url' => $webhookUrl,
            ],
            'description' => sprintf(
                '%s - Recurring Payment',
                $plan->getName()
            ),
            'metadata' => [
                'subscription_id' => $subscription->getId(),
                'team_id' => $team->getId(),
                'type' => 'recurring',
            ],
        ];

        if ($subscription->getTapCustomerId()) {
            $chargeData['customer'] = ['id' => $subscription->getTapCustomerId()];
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/charges', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $chargeData,
            ]);

            $data = $response->toArray();

            $this->logInfo('Recurring payment charged', [
                'charge_id' => $data['id'] ?? null,
                'subscription_id' => $subscription->getId(),
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logError('Failed to charge saved payment method', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->getId(),
            ]);

            throw new \RuntimeException('Tap: Failed to charge payment method: ' . $e->getMessage());
        }
    }

    public function validateWebhookSignature(array $payload, string $signature): bool
    {
        // Extract required fields from payload for Tap signature
        // Tap uses a specific construction for the hash string
        $id = $payload['id'] ?? '';
        $amount = isset($payload['amount']) ? number_format((float) $payload['amount'], 2, '.', '') : '';
        $currency = $payload['currency'] ?? '';
        $gatewayReference = $payload['reference']['gateway'] ?? '';
        $paymentReference = $payload['reference']['payment'] ?? '';
        $status = $payload['status'] ?? '';
        $created = $payload['transaction']['created'] ?? '';

        $hashString = 'x_id' . $id .
                     'x_amount' . $amount .
                     'x_currency' . $currency .
                     'x_gateway_reference' . $gatewayReference .
                     'x_payment_reference' . $paymentReference .
                     'x_status' . $status .
                     'x_created' . $created;

        $computedHash = hash_hmac('sha256', $hashString, $this->secretKey);

        return hash_equals($computedHash, $signature);
    }
    
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}
