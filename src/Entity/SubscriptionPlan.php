<?php

namespace App\Entity;

use App\Repository\SubscriptionPlanRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SubscriptionPlanRepository::class)]
#[ORM\Table(name: 'subscription_plan')]
#[ORM\UniqueConstraint(name: 'UNIQ_PLAN_SLUG', fields: ['slug'])]
#[UniqueEntity(fields: ['slug'], message: 'This slug is already in use.')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: ['groups' => ['plan:read']],
    denormalizationContext: ['groups' => ['plan:write']],
)]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['plan:read', 'plan:write'])]
    #[ORM\Column(length: 3)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?string $currency = 'USD';

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

    /**
     * Get formatted price for display
     */
    public function getFormattedPrice(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'SAR' => 'SAR ',
            'AED' => 'AED ',
            'KWD' => 'KWD ',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';
        $price = number_format((float) $this->price, 2);
        
        return $symbol . $price . '/' . ($this->billingInterval === 'yearly' ? 'year' : 'month');
    }
    #[Groups(['plan:read', 'plan:write'])]
    private ?string $billingInterval = 'monthly'; // monthly, yearly

    #[ORM\Column(nullable: true)]
    #[Groups(['plan:read', 'plan:write'])]
    private ?int $teamMemberLimit = null;

    #[ORM\Column]
    #[Groups(['plan:read', 'plan:write'])]
    private ?bool $isActive = true;

    #[ORM\Column]
    #[Groups(['plan:read', 'plan:write'])]
    private ?int $displayOrder = 0;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, PlanFeature>
     */
    #[ORM\OneToMany(targetEntity: PlanFeature::class, mappedBy: 'plan', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $planFeatures;

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'plan')]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->planFeatures = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getBillingInterval(): ?string
    {
        return $this->billingInterval;
    }

    public function setBillingInterval(string $billingInterval): static
    {
        $this->billingInterval = $billingInterval;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getTeamMemberLimit(): ?int
    {
        return $this->teamMemberLimit;
    }

    public function setTeamMemberLimit(?int $teamMemberLimit): static
    {
        $this->teamMemberLimit = $teamMemberLimit;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDisplayOrder(): ?int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;
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
     * @return Collection<int, PlanFeature>
     */
    public function getPlanFeatures(): Collection
    {
        return $this->planFeatures;
    }

    public function addPlanFeature(PlanFeature $planFeature): static
    {
        if (!$this->planFeatures->contains($planFeature)) {
            $this->planFeatures->add($planFeature);
            $planFeature->setPlan($this);
        }

        return $this;
    }

    public function removePlanFeature(PlanFeature $planFeature): static
    {
        if ($this->planFeatures->removeElement($planFeature)) {
            if ($planFeature->getPlan() === $this) {
                $planFeature->setPlan(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setPlan($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->getPlan() === $this) {
                $subscription->setPlan(null);
            }
        }

        return $this;
    }



    /**
     * Check if this plan has a specific feature
     */
    public function hasFeature(string $featureSlug): bool
    {
        foreach ($this->planFeatures as $planFeature) {
            if ($planFeature->getFeature()->getSlug() === $featureSlug && $planFeature->isEnabled()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the value of a specific feature
     */
    public function getFeatureValue(string $featureSlug): mixed
    {
        foreach ($this->planFeatures as $planFeature) {
            if ($planFeature->getFeature()->getSlug() === $featureSlug && $planFeature->isEnabled()) {
                return $planFeature->getValue();
            }
        }

        return null;
    }
}
