<?php

namespace App\Entity;

use App\Repository\PlanFeatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PlanFeatureRepository::class)]
#[ORM\Table(name: 'plan_feature')]
#[ORM\UniqueConstraint(name: 'UNIQ_PLAN_FEATURE', fields: ['plan_id', 'feature_id'])]
class PlanFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class, inversedBy: 'planFeatures')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SubscriptionPlan $plan = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionFeature::class, inversedBy: 'planFeatures')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['plan:read'])]
    private ?SubscriptionFeature $feature = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['plan:read'])]
    private ?string $value = null;

    #[ORM\Column]
    #[Groups(['plan:read'])]
    private ?bool $enabled = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getFeature(): ?SubscriptionFeature
    {
        return $this->feature;
    }

    public function setFeature(?SubscriptionFeature $feature): static
    {
        $this->feature = $feature;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

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
}
