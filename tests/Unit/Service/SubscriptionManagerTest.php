<?php

namespace App\Tests\Unit\Service;

use App\Entity\Subscription;
use App\Entity\SubscriptionPlan;
use App\Entity\Team;
use App\Repository\PlanFeatureRepository;
use App\Repository\SubscriptionRepository;
use App\Service\SubscriptionManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SubscriptionManagerTest extends TestCase
{
    private $entityManager;
    private $subscriptionRepository;
    private $planFeatureRepository;
    private $subscriptionManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->subscriptionRepository = $this->createMock(SubscriptionRepository::class);
        $this->planFeatureRepository = $this->createMock(PlanFeatureRepository::class);

        $this->subscriptionManager = new SubscriptionManager(
            $this->entityManager,
            $this->subscriptionRepository,
            $this->planFeatureRepository
        );
    }

    public function testCreateSubscriptionCreatesActiveSubscription()
    {
        $team = new Team();
        $plan = new SubscriptionPlan();
        $plan->setBillingInterval('monthly');

        $this->subscriptionRepository->expects($this->once())
            ->method('findActiveByTeam')
            ->with($team)
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Subscription::class));
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        $subscription = $this->subscriptionManager->createSubscription($team, $plan, false);

        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->getStatus());
        $this->assertNotNull($subscription->getCurrentPeriodStart());
        $this->assertNotNull($subscription->getCurrentPeriodEnd());
    }

    public function testCreateSubscriptionCreatesTrialSubscription()
    {
        $team = new Team();
        $plan = new SubscriptionPlan();
        
        $this->subscriptionRepository->expects($this->once())
            ->method('findActiveByTeam')
            ->with($team)
            ->willReturn(null);

        $subscription = $this->subscriptionManager->createSubscription($team, $plan, true, 14);

        $this->assertEquals(Subscription::STATUS_TRIAL, $subscription->getStatus());
        $this->assertNotNull($subscription->getTrialEndsAt());
    }

    public function testChangePlanUpdatesSubscription()
    {
        $subscription = new Subscription();
        $oldPlan = new SubscriptionPlan();
        $oldPlan->setBillingInterval('monthly');
        
        $reflector = new \ReflectionClass($oldPlan);
        $property = $reflector->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($oldPlan, 1);
        
        $subscription->setPlan($oldPlan);

        $newPlan = new SubscriptionPlan();
        $newPlan->setBillingInterval('yearly');
        $property->setValue($newPlan, 2);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->subscriptionManager->changePlan($subscription, $newPlan);

        $this->assertEquals($newPlan, $subscription->getPlan());
        // Should update period end for yearly billing
        $this->assertNotNull($subscription->getCurrentPeriodEnd()); 
    }

    public function testCancelSubscriptionImmediately()
    {
        $subscription = new Subscription();
        $subscription->setStatus(Subscription::STATUS_ACTIVE);
        
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->subscriptionManager->cancelSubscription($subscription, true);

        $this->assertEquals(Subscription::STATUS_CANCELED, $subscription->getStatus());
        $this->assertNotNull($subscription->getEndsAt());
    }
}
