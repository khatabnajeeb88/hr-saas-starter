<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payment')]
class Payment
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CAPTURED = 'captured';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Subscription::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Subscription $subscription = null;

    #[ORM\Column(length: 255)]
    private ?string $tapChargeId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $cardLastFour = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cardBrand = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gatewayReference = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $gatewayResponse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getTapChargeId(): ?string
    {
        return $this->tapChargeId;
    }

    public function setTapChargeId(string $tapChargeId): static
    {
        $this->tapChargeId = $tapChargeId;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCardLastFour(): ?string
    {
        return $this->cardLastFour;
    }

    public function setCardLastFour(?string $cardLastFour): static
    {
        $this->cardLastFour = $cardLastFour;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function setCardBrand(?string $cardBrand): static
    {
        $this->cardBrand = $cardBrand;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getGatewayReference(): ?string
    {
        return $this->gatewayReference;
    }

    public function setGatewayReference(?string $gatewayReference): static
    {
        $this->gatewayReference = $gatewayReference;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): static
    {
        $this->paymentReference = $paymentReference;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getGatewayResponse(): ?array
    {
        return $this->gatewayResponse;
    }

    public function setGatewayResponse(?array $gatewayResponse): static
    {
        $this->gatewayResponse = $gatewayResponse;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): static
    {
        $this->failureReason = $failureReason;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Check if payment was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_CAPTURED;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get formatted amount for display
     */
    public function getFormattedAmount(): string
    {
        return number_format((float) $this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get masked card number
     */
    public function getMaskedCardNumber(): ?string
    {
        if (!$this->cardLastFour) {
            return null;
        }

        return '**** **** **** ' . $this->cardLastFour;
    }
}
