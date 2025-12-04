<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\SubscriptionPlan;
use App\Entity\Team;
use App\Repository\PlanFeatureRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionRepository $subscriptionRepository,
        private PlanFeatureRepository $planFeatureRepository,
    ) {
    }

    /**
     * Create a new subscription for a team
     */
    public function createSubscription(
        Team $team,
        SubscriptionPlan $plan,
        bool $trial = false,
        int $trialDays = 14
    ): Subscription {
        // Check if team already has a subscription
        $existingSubscription = $this->subscriptionRepository->findActiveByTeam($team);
        if ($existingSubscription) {
            throw new \RuntimeException('Team already has an active subscription');
        }

        $subscription = new Subscription();
        $subscription->setTeam($team);
        $subscription->setPlan($plan);

        $now = new \DateTimeImmutable();

        if ($trial) {
            $subscription->setStatus(Subscription::STATUS_TRIAL);
            $subscription->setTrialEndsAt($now->modify("+{$trialDays} days"));
            $subscription->setCurrentPeriodStart($now);
            $subscription->setCurrentPeriodEnd($now->modify("+{$trialDays} days"));
        } else {
            $subscription->setStatus(Subscription::STATUS_ACTIVE);
            $subscription->setCurrentPeriodStart($now);
            
            // Set period end based on billing interval
            $periodEnd = $plan->getBillingInterval() === 'yearly' 
                ? $now->modify('+1 year')
                : $now->modify('+1 month');
            
            $subscription->setCurrentPeriodEnd($periodEnd);
            $subscription->setNextBillingDate($periodEnd);
        }

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): void
    {
        $now = new \DateTimeImmutable();
        $subscription->setCanceledAt($now);

        if ($immediately) {
            $subscription->setStatus(Subscription::STATUS_CANCELED);
            $subscription->setEndsAt($now);
        } else {
            // Cancel at end of current period
            $subscription->setEndsAt($subscription->getCurrentPeriodEnd());
            $subscription->setAutoRenew(false);
        }

        $this->entityManager->flush();
    }

    /**
     * Renew a subscription for the next billing cycle
     */
    public function renewSubscription(Subscription $subscription): void
    {
        if (!$subscription->isAutoRenew()) {
            throw new \RuntimeException('Subscription is not set to auto-renew');
        }

        $plan = $subscription->getPlan();
        $currentPeriodEnd = $subscription->getCurrentPeriodEnd();

        // Calculate next period end
        $nextPeriodEnd = $plan->getBillingInterval() === 'yearly'
            ? $currentPeriodEnd->modify('+1 year')
            : $currentPeriodEnd->modify('+1 month');

        $subscription->setCurrentPeriodStart($currentPeriodEnd);
        $subscription->setCurrentPeriodEnd($nextPeriodEnd);
        $subscription->setStatus(Subscription::STATUS_ACTIVE);

        $this->entityManager->flush();
    }

    /**
     * Change subscription plan (upgrade/downgrade)
     */
    public function changePlan(Subscription $subscription, SubscriptionPlan $newPlan): void
    {
        if ($subscription->getPlan()->getId() === $newPlan->getId()) {
            throw new \RuntimeException('New plan is the same as current plan');
        }

        $oldPlan = $subscription->getPlan();
        $subscription->setPlan($newPlan);

        // If changing billing interval, adjust the period end
        if ($oldPlan->getBillingInterval() !== $newPlan->getBillingInterval()) {
            $now = new \DateTimeImmutable();
            $periodEnd = $newPlan->getBillingInterval() === 'yearly'
                ? $now->modify('+1 year')
                : $now->modify('+1 month');
            
            $subscription->setCurrentPeriodStart($now);
            $subscription->setCurrentPeriodEnd($periodEnd);
        }

        // Reset trial status if on trial
        if ($subscription->onTrial()) {
            $subscription->setStatus(Subscription::STATUS_ACTIVE);
            $subscription->setTrialEndsAt(null);
        }

        $this->entityManager->flush();
    }

    /**
     * Check if team has access to a specific feature
     */
    public function hasFeature(Team $team, string $featureSlug): bool
    {
        $subscription = $team->getSubscription();
        
        if (!$subscription || !$subscription->isActive()) {
            return false;
        }

        $plan = $subscription->getPlan();
        return $this->planFeatureRepository->hasFeature($plan, $featureSlug);
    }

    /**
     * Get the value of a feature for a team
     */
    public function getFeatureValue(Team $team, string $featureSlug): mixed
    {
        $subscription = $team->getSubscription();
        
        if (!$subscription || !$subscription->isActive()) {
            return null;
        }

        $plan = $subscription->getPlan();
        return $this->planFeatureRepository->findFeatureValue($plan, $featureSlug);
    }

    /**
     * Check if team has an active subscription
     */
    public function isSubscriptionActive(Team $team): bool
    {
        return $team->hasActiveSubscription();
    }

    /**
     * Check if team can add more members based on plan limits
     */
    public function canAddTeamMember(Team $team): bool
    {
        $subscription = $team->getSubscription();
        
        if (!$subscription || !$subscription->isActive()) {
            return false;
        }

        $plan = $subscription->getPlan();
        $memberLimit = $plan->getTeamMemberLimit();

        // Null means unlimited
        if ($memberLimit === null) {
            return true;
        }

        $currentMemberCount = $team->getMembers()->count();
        return $currentMemberCount < $memberLimit;
    }

    /**
     * Resume a canceled subscription
     */
    public function resumeSubscription(Subscription $subscription): void
    {
        if ($subscription->getStatus() !== Subscription::STATUS_CANCELED) {
            throw new \RuntimeException('Only canceled subscriptions can be resumed');
        }

        // Check if subscription hasn't ended yet
        $now = new \DateTimeImmutable();
        if ($subscription->getEndsAt() && $subscription->getEndsAt() < $now) {
            throw new \RuntimeException('Subscription has already ended and cannot be resumed');
        }

        $subscription->setStatus(Subscription::STATUS_ACTIVE);
        $subscription->setCanceledAt(null);
        $subscription->setEndsAt(null);
        $subscription->setAutoRenew(true);

        $this->entityManager->flush();
    }

    /**
     * Process expired trials and convert them to expired status
     */
    public function processExpiredTrials(): int
    {
        $expiredTrials = $this->subscriptionRepository->findExpiredTrials();
        $count = 0;

        foreach ($expiredTrials as $subscription) {
            $subscription->setStatus(Subscription::STATUS_EXPIRED);
            $count++;
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Get subscription statistics for a team
     */
    public function getSubscriptionStats(Team $team): array
    {
        $subscription = $team->getSubscription();

        if (!$subscription) {
            return [
                'has_subscription' => false,
            ];
        }

        return [
            'has_subscription' => true,
            'plan_name' => $subscription->getPlan()->getName(),
            'status' => $subscription->getStatus(),
            'is_active' => $subscription->isActive(),
            'on_trial' => $subscription->onTrial(),
            'days_remaining' => $subscription->daysRemaining(),
            'ending_soon' => $subscription->endingSoon(),
            'auto_renew' => $subscription->isAutoRenew(),
            'current_period_end' => $subscription->getCurrentPeriodEnd(),
            'member_limit' => $subscription->getPlan()->getTeamMemberLimit(),
            'current_members' => $team->getMembers()->count(),
        ];
    }
}
