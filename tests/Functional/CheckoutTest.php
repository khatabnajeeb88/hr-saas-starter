<?php

namespace App\Tests\Functional;

use App\Entity\SubscriptionPlan;
use App\Entity\User;
use App\Service\TapPaymentService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CheckoutTest extends WebTestCase
{
    public function testCheckoutInitiationRedirects()
    {
        $client = static::createClient();

        // Mock TapPaymentService
        $paymentService = $this->createMock(TapPaymentService::class);
        $paymentService->method('createCharge')
            ->willReturn([
                'transaction' => [
                    'url' => 'https://sandbox.tap.company/pay/test_url'
                ]
            ]);

        $client->getContainer()->set(TapPaymentService::class, $paymentService);

        // Create User & Plan
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        
        $user = new User();
        $user->setEmail('checkout_' . uniqid() . '@example.com');
        $user->setName('Checkout User');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $em->persist($user);

        // Create Team
        $team = new \App\Entity\Team();
        $team->setName('Checkout Team');
        $team->setSlug('checkout-team-'.uniqid());
        $team->setOwner($user);
        $em->persist($team);

        $member = new \App\Entity\TeamMember();
        $member->setTeam($team);
        $member->setUser($user);
        $member->setRole(\App\Entity\TeamMember::ROLE_OWNER);
        $em->persist($member);

        $plan = new SubscriptionPlan();
        $plan->setName('Pro Plan');
        $plan->setSlug('pro-monthly-'.uniqid());
        $plan->setPrice(29.00);
        $plan->setCurrency('USD');
        $plan->setBillingInterval('monthly');

        $em->persist($plan);

        // Create Subscription
        $subscription = new \App\Entity\Subscription();
        $subscription->setTeam($team);
        $subscription->setPlan($plan);
        $subscription->setStatus(\App\Entity\Subscription::STATUS_TRIAL);

        $subscription->setCurrentPeriodStart(new \DateTimeImmutable());
        $subscription->setCurrentPeriodEnd(new \DateTimeImmutable('+1 month'));
        $em->persist($subscription);
        
        $em->flush();

        $client->loginUser($user);
        
        // Attempt checkout
        $client->request('GET', '/en/payment/checkout/' . $subscription->getId());

        $this->assertResponseRedirects('https://sandbox.tap.company/pay/test_url');
    }
}
