<?php

namespace App\Service;

use App\Payment\Gateway\PaymentGatewayInterface;
use App\Payment\Gateway\StripeGateway;
use App\Payment\Gateway\TapGateway;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class PaymentGatewayFactory
{
    /** @var PaymentGatewayInterface[] */
    private array $gateways = [];

    public function __construct(
        iterable $gateways,
        private string $defaultGatewayName = 'tap',
    ) {
        foreach ($gateways as $gateway) {
            $this->gateways[$gateway->getName()] = $gateway;
        }
    }

    public function getGateway(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException(sprintf('Payment gateway "%s" is not supported.', $name));
        }

        return $this->gateways[$name];
    }

    /**
     * Get default gateway (configurable)
     */
    public function getDefaultGateway(): PaymentGatewayInterface
    {
        if (isset($this->gateways[$this->defaultGatewayName])) {
            return $this->gateways[$this->defaultGatewayName];
        }

        // Fallback or throw
        if (empty($this->gateways)) {
             throw new \RuntimeException('No payment gateways configured.');
        }
        
        return reset($this->gateways);
    }
    
    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
