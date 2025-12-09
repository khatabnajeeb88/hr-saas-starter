<?php

namespace App\Payment\Gateway;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Log error helper
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error(sprintf('[%s] %s', $this->getName(), $message), $context);
    }

    /**
     * Log info helper
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info(sprintf('[%s] %s', $this->getName(), $message), $context);
    }
}
