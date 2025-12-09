<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Subscription;
use App\Entity\Team;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TapPaymentService
{
    private string $secretKey;
    private string $publicKey;
    private string $apiUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private PaymentRepository $paymentRepository,
        private LoggerInterface $logger,
        string $tapSecretKey,
        string $tapPublicKey,
        string $tapApiUrl,
    ) {
        $this->secretKey = $tapSecretKey;
        $this->publicKey = $tapPublicKey;
        $this->apiUrl = $tapApiUrl;
    }

    /**
     * Create a charge with Tap API
     */
    public function createCharge(
        Subscription $subscription,
        string $redirectUrl,
        string $webhookUrl,
        ?string $savedCardToken = null
    ): array {
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

            $this->logger->info('Tap charge created', [
                'charge_id' => $data['id'] ?? null,
                'subscription_id' => $subscription->getId(),
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create Tap charge', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->getId(),
            ]);

            throw new \RuntimeException('Failed to create payment charge: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve charge details from Tap
     */
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
            $this->logger->error('Failed to retrieve Tap charge', [
                'error' => $e->getMessage(),
                'charge_id' => $chargeId,
            ]);

            throw new \RuntimeException('Failed to retrieve charge: ' . $e->getMessage());
        }
    }

    /**
     * Process refund for a charge
     */
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

            $this->logger->info('Tap refund processed', [
                'refund_id' => $data['id'] ?? null,
                'charge_id' => $chargeId,
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Failed to process Tap refund', [
                'error' => $e->getMessage(),
                'charge_id' => $chargeId,
            ]);

            throw new \RuntimeException('Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature(array $payload, string $receivedHashString): bool
    {
        // Extract required fields from payload
        $id = $payload['id'] ?? '';
        $amount = isset($payload['amount']) ? number_format((float) $payload['amount'], 2, '.', '') : '';
        $currency = $payload['currency'] ?? '';
        $gatewayReference = $payload['reference']['gateway'] ?? '';
        $paymentReference = $payload['reference']['payment'] ?? '';
        $status = $payload['status'] ?? '';
        $created = $payload['transaction']['created'] ?? '';

        // Build hash string
        $hashString = 'x_id' . $id .
                     'x_amount' . $amount .
                     'x_currency' . $currency .
                     'x_gateway_reference' . $gatewayReference .
                     'x_payment_reference' . $paymentReference .
                     'x_status' . $status .
                     'x_created' . $created;

        // Compute HMAC
        $computedHash = hash_hmac('sha256', $hashString, $this->secretKey);

        $isValid = hash_equals($computedHash, $receivedHashString);

        if (!$isValid) {
            $this->logger->warning('Invalid webhook signature', [
                'received' => $receivedHashString,
                'computed' => $computedHash,
            ]);
        }

        return $isValid;
    }

    /**
     * Create payment record from charge data
     */
    public function createPaymentFromCharge(Subscription $subscription, array $chargeData): Payment
    {
        $payment = new Payment();
        $payment->setSubscription($subscription);
        $payment->setChargeId($chargeData['id']);
        $payment->setAmount($chargeData['amount']);
        $payment->setCurrency($chargeData['currency']);
        $payment->setStatus($this->mapTapStatusToPaymentStatus($chargeData['status']));

        // Set payment method details if available
        if (isset($chargeData['source']['payment_method'])) {
            $payment->setPaymentMethod($chargeData['source']['payment_method']);
        }

        if (isset($chargeData['card'])) {
            $payment->setCardLastFour($chargeData['card']['last_four'] ?? null);
            $payment->setCardBrand($chargeData['card']['brand'] ?? null);
        }

        // Set references
        if (isset($chargeData['reference'])) {
            $payment->setGatewayReference($chargeData['reference']['gateway'] ?? null);
            $payment->setPaymentReference($chargeData['reference']['payment'] ?? null);
        }

        // Store full response
        $payment->setGatewayResponse($chargeData);

        // Set failure reason if failed
        if ($chargeData['status'] === 'FAILED' && isset($chargeData['response']['message'])) {
            $payment->setFailureReason($chargeData['response']['message']);
        }

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * Map Tap status to Payment status
     */
    private function mapTapStatusToPaymentStatus(string $tapStatus): string
    {
        return match (strtoupper($tapStatus)) {
            'CAPTURED' => Payment::STATUS_CAPTURED,
            'FAILED', 'DECLINED' => Payment::STATUS_FAILED,
            'CANCELLED' => Payment::STATUS_CANCELLED,
            'REFUNDED' => Payment::STATUS_REFUNDED,
            default => Payment::STATUS_PENDING,
        };
    }

    /**
     * Save payment method (card token) to subscription
     */
    public function savePaymentMethod(Subscription $subscription, string $cardToken, ?string $customerId = null): void
    {
        $subscription->setPaymentMethodId($cardToken);
        
        if ($customerId) {
            $subscription->setTapCustomerId($customerId);
        }

        $this->entityManager->flush();

        $this->logger->info('Payment method saved', [
            'subscription_id' => $subscription->getId(),
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Charge a saved payment method
     */
    public function chargePaymentMethod(Subscription $subscription, string $webhookUrl): array
    {
        if (!$subscription->getPaymentMethodId()) {
            throw new \RuntimeException('No payment method saved for this subscription');
        }

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

            $this->logger->info('Recurring payment charged', [
                'charge_id' => $data['id'] ?? null,
                'subscription_id' => $subscription->getId(),
            ]);

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Failed to charge saved payment method', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->getId(),
            ]);

            throw new \RuntimeException('Failed to charge payment method: ' . $e->getMessage());
        }
    }

    /**
     * Get public key for frontend
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}
