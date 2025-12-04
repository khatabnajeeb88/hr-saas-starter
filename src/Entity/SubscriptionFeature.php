<?php

namespace App\Entity;

use App\Repository\SubscriptionFeatureRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: SubscriptionFeatureRepository::class)]
#[ORM\Table(name: 'subscription_feature')]
#[ORM\UniqueConstraint(name: 'UNIQ_FEATURE_SLUG', fields: ['slug'])]
#[UniqueEntity(fields: ['slug'], message: 'This slug is already in use.')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: ['groups' => ['feature:read']],
    denormalizationContext: ['groups' => ['feature:write']],
)]
class SubscriptionFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['feature:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['feature:read', 'feature:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['feature:read', 'feature:write'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['feature:read', 'feature:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Groups(['feature:read', 'feature:write'])]
    private ?string $featureType = 'boolean'; // boolean, limit, quota

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['feature:read', 'feature:write'])]
    private ?string $defaultValue = null;

    #[ORM\Column]
    #[Groups(['feature:read', 'feature:write'])]
    private ?bool $isActive = true;

    #[ORM\Column]
    #[Groups(['feature:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['feature:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, PlanFeature>
     */
    #[ORM\OneToMany(targetEntity: PlanFeature::class, mappedBy: 'feature', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $planFeatures;

    public function __construct()
    {
        $this->planFeatures = new ArrayCollection();
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

    public function getFeatureType(): ?string
    {
        return $this->featureType;
    }

    public function setFeatureType(string $featureType): static
    {
        $this->featureType = $featureType;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
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
            $planFeature->setFeature($this);
        }

        return $this;
    }

    public function removePlanFeature(PlanFeature $planFeature): static
    {
        if ($this->planFeatures->removeElement($planFeature)) {
            if ($planFeature->getFeature() === $this) {
                $planFeature->setFeature(null);
            }
        }

        return $this;
    }
}
