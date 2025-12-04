<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'subscription')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
    ],
    normalizationContext: ['groups' => ['subscription:read']],
    denormalizationContext: ['groups' => ['subscription:write']],
)]
class Subscription
{
    public const STATUS_TRIAL = 'trial';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'subscription', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?SubscriptionPlan $plan = null;

    #[ORM\Column(length: 50)]
    #[Groups(['subscription:read'])]
    private ?string $status = self::STATUS_TRIAL;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $canceledAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?bool $autoRenew = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tapCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentMethodId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastPaymentAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $nextBillingDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $gracePeriodEndsAt = null;

    #[ORM\Column]
    private int $retryCount = 0;

    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['subscription:read'])]
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

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlan $plan): static
    {
        $this->plan = $plan;
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

    public function getTrialEndsAt(): ?\DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): static
    {
        $this->trialEndsAt = $trialEndsAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCurrentPeriodStart(): ?\DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function setCurrentPeriodStart(?\DateTimeImmutable $currentPeriodStart): static
    {
        $this->currentPeriodStart = $currentPeriodStart;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCurrentPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriodEnd(?\DateTimeImmutable $currentPeriodEnd): static
    {
        $this->currentPeriodEnd = $currentPeriodEnd;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCanceledAt(): ?\DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?\DateTimeImmutable $canceledAt): static
    {
        $this->canceledAt = $canceledAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isAutoRenew(): ?bool
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(bool $autoRenew): static
    {
        $this->autoRenew = $autoRenew;
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

    public function getTapCustomerId(): ?string
    {
        return $this->tapCustomerId;
    }

    public function setTapCustomerId(?string $tapCustomerId): static
    {
        $this->tapCustomerId = $tapCustomerId;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(?string $paymentMethodId): static
    {
        $this->paymentMethodId = $paymentMethodId;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getLastPaymentAt(): ?\DateTimeImmutable
    {
        return $this->lastPaymentAt;
    }

    public function setLastPaymentAt(?\DateTimeImmutable $lastPaymentAt): static
    {
        $this->lastPaymentAt = $lastPaymentAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getNextBillingDate(): ?\DateTimeImmutable
    {
        return $this->nextBillingDate;
    }

    public function setNextBillingDate(?\DateTimeImmutable $nextBillingDate): static
    {
        $this->nextBillingDate = $nextBillingDate;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getGracePeriodEndsAt(): ?\DateTimeImmutable
    {
        return $this->gracePeriodEndsAt;
    }

    public function setGracePeriodEndsAt(?\DateTimeImmutable $gracePeriodEndsAt): static
    {
        $this->gracePeriodEndsAt = $gracePeriodEndsAt;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): static
    {
        $this->retryCount = $retryCount;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function incrementRetryCount(): static
    {
        $this->retryCount++;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function resetRetryCount(): static
    {
        $this->retryCount = 0;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Check if subscription is currently active
     */
    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_TRIAL, self::STATUS_ACTIVE]);
    }

    /**
     * Check if subscription is on trial
     */
    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL 
            && $this->trialEndsAt !== null 
            && $this->trialEndsAt > new \DateTimeImmutable();
    }

    /**
     * Check if subscription is canceled
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Get days remaining in current period
     */
    public function daysRemaining(): int
    {
        if ($this->currentPeriodEnd === null) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        $interval = $now->diff($this->currentPeriodEnd);
        
        return $interval->days;
    }

    /**
     * Check if subscription will end soon (within 7 days)
     */
    public function endingSoon(): bool
    {
        return $this->daysRemaining() <= 7 && $this->daysRemaining() > 0;
    }
}
