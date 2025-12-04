<?php

namespace App\Service;

use App\Entity\PlanFeature;
use App\Entity\SubscriptionFeature;
use App\Entity\SubscriptionPlan;
use App\Repository\SubscriptionFeatureRepository;
use App\Repository\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class PlanManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionPlanRepository $planRepository,
        private SubscriptionFeatureRepository $featureRepository,
        private SluggerInterface $slugger,
    ) {
    }

    /**
     * Create a new subscription plan
     */
    public function createPlan(array $data): SubscriptionPlan
    {
        $plan = new SubscriptionPlan();
        $plan->setName($data['name']);
        
        // Generate slug from name if not provided
        $slug = $data['slug'] ?? $this->slugger->slug($data['name'])->lower()->toString();
        $plan->setSlug($slug);
        
        $plan->setDescription($data['description'] ?? null);
        $plan->setPrice($data['price']);
        $plan->setBillingInterval($data['billing_interval'] ?? 'monthly');
        $plan->setTeamMemberLimit($data['team_member_limit'] ?? null);
        $plan->setIsActive($data['is_active'] ?? true);
        $plan->setDisplayOrder($data['display_order'] ?? 0);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Update an existing subscription plan
     */
    public function updatePlan(SubscriptionPlan $plan, array $data): SubscriptionPlan
    {
        if (isset($data['name'])) {
            $plan->setName($data['name']);
        }

        if (isset($data['slug'])) {
            $plan->setSlug($data['slug']);
        }

        if (isset($data['description'])) {
            $plan->setDescription($data['description']);
        }

        if (isset($data['price'])) {
            $plan->setPrice($data['price']);
        }

        if (isset($data['billing_interval'])) {
            $plan->setBillingInterval($data['billing_interval']);
        }

        if (isset($data['team_member_limit'])) {
            $plan->setTeamMemberLimit($data['team_member_limit']);
        }

        if (isset($data['is_active'])) {
            $plan->setIsActive($data['is_active']);
        }

        if (isset($data['display_order'])) {
            $plan->setDisplayOrder($data['display_order']);
        }

        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Add a feature to a plan
     */
    public function addFeatureToPlan(
        SubscriptionPlan $plan,
        SubscriptionFeature $feature,
        mixed $value = null,
        bool $enabled = true
    ): PlanFeature {
        // Check if feature already exists for this plan
        foreach ($plan->getPlanFeatures() as $existingPlanFeature) {
            if ($existingPlanFeature->getFeature()->getId() === $feature->getId()) {
                throw new \RuntimeException('Feature already exists for this plan');
            }
        }

        $planFeature = new PlanFeature();
        $planFeature->setPlan($plan);
        $planFeature->setFeature($feature);
        $planFeature->setValue($value);
        $planFeature->setEnabled($enabled);

        $plan->addPlanFeature($planFeature);

        $this->entityManager->persist($planFeature);
        $this->entityManager->flush();

        return $planFeature;
    }

    /**
     * Remove a feature from a plan
     */
    public function removeFeatureFromPlan(SubscriptionPlan $plan, SubscriptionFeature $feature): void
    {
        foreach ($plan->getPlanFeatures() as $planFeature) {
            if ($planFeature->getFeature()->getId() === $feature->getId()) {
                $plan->removePlanFeature($planFeature);
                $this->entityManager->remove($planFeature);
                $this->entityManager->flush();
                return;
            }
        }

        throw new \RuntimeException('Feature not found in plan');
    }

    /**
     * Update a feature value for a plan
     */
    public function updatePlanFeature(
        SubscriptionPlan $plan,
        SubscriptionFeature $feature,
        mixed $value = null,
        ?bool $enabled = null
    ): void {
        foreach ($plan->getPlanFeatures() as $planFeature) {
            if ($planFeature->getFeature()->getId() === $feature->getId()) {
                if ($value !== null) {
                    $planFeature->setValue($value);
                }
                if ($enabled !== null) {
                    $planFeature->setEnabled($enabled);
                }
                $this->entityManager->flush();
                return;
            }
        }

        throw new \RuntimeException('Feature not found in plan');
    }

    /**
     * Get all active plans
     */
    public function getActivePlans(): array
    {
        return $this->planRepository->findActivePlans();
    }

    /**
     * Delete a plan (only if no active subscriptions)
     */
    public function deletePlan(SubscriptionPlan $plan): void
    {
        // Check if plan has active subscriptions
        $activeSubscriptions = $plan->getSubscriptions()->filter(
            fn($subscription) => $subscription->isActive()
        );

        if ($activeSubscriptions->count() > 0) {
            throw new \RuntimeException('Cannot delete plan with active subscriptions');
        }

        $this->entityManager->remove($plan);
        $this->entityManager->flush();
    }

    /**
     * Deactivate a plan (soft delete)
     */
    public function deactivatePlan(SubscriptionPlan $plan): void
    {
        $plan->setIsActive(false);
        $this->entityManager->flush();
    }

    /**
     * Activate a plan
     */
    public function activatePlan(SubscriptionPlan $plan): void
    {
        $plan->setIsActive(true);
        $this->entityManager->flush();
    }

    /**
     * Create a new feature
     */
    public function createFeature(array $data): SubscriptionFeature
    {
        $feature = new SubscriptionFeature();
        $feature->setName($data['name']);
        
        // Generate slug from name if not provided
        $slug = $data['slug'] ?? $this->slugger->slug($data['name'])->lower()->toString();
        $feature->setSlug($slug);
        
        $feature->setDescription($data['description'] ?? null);
        $feature->setFeatureType($data['feature_type'] ?? 'boolean');
        $feature->setDefaultValue($data['default_value'] ?? null);
        $feature->setIsActive($data['is_active'] ?? true);

        $this->entityManager->persist($feature);
        $this->entityManager->flush();

        return $feature;
    }

    /**
     * Duplicate a plan with all its features
     */
    public function duplicatePlan(SubscriptionPlan $sourcePlan, string $newName): SubscriptionPlan
    {
        $newPlan = new SubscriptionPlan();
        $newPlan->setName($newName);
        $newPlan->setSlug($this->slugger->slug($newName)->lower()->toString());
        $newPlan->setDescription($sourcePlan->getDescription());
        $newPlan->setPrice($sourcePlan->getPrice());
        $newPlan->setBillingInterval($sourcePlan->getBillingInterval());
        $newPlan->setTeamMemberLimit($sourcePlan->getTeamMemberLimit());
        $newPlan->setIsActive(false); // Start as inactive
        $newPlan->setDisplayOrder($sourcePlan->getDisplayOrder() + 1);

        $this->entityManager->persist($newPlan);

        // Copy all features
        foreach ($sourcePlan->getPlanFeatures() as $sourcePlanFeature) {
            $newPlanFeature = new PlanFeature();
            $newPlanFeature->setPlan($newPlan);
            $newPlanFeature->setFeature($sourcePlanFeature->getFeature());
            $newPlanFeature->setValue($sourcePlanFeature->getValue());
            $newPlanFeature->setEnabled($sourcePlanFeature->isEnabled());

            $this->entityManager->persist($newPlanFeature);
        }

        $this->entityManager->flush();

        return $newPlan;
    }
}
