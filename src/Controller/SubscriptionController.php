<?php

namespace App\Controller;

use App\Entity\Team;
use App\Repository\SubscriptionPlanRepository;
use App\Service\PlanManager;
use App\Service\SubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subscription')]
#[IsGranted('ROLE_USER')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private PlanManager $planManager,
        private SubscriptionPlanRepository $planRepository,
    ) {
    }

    #[Route('/plans', name: 'subscription_plans')]
    public function plans(): Response
    {
        $plans = $this->planManager->getActivePlans();

        return $this->render('subscription/plans.html.twig', [
            'plans' => $plans,
        ]);
    }

    #[Route('/current', name: 'subscription_current')]
    public function current(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Get user's teams - for now, we'll use the first team
        // In a real app, you'd have team selection logic
        $teams = $user->getTeamMembers();
        
        if ($teams->isEmpty()) {
            $this->addFlash('warning', 'You are not a member of any team.');
            return $this->redirectToRoute('subscription_plans');
        }

        $team = $teams->first()->getTeam();
        $subscription = $team->getSubscription();

        if (!$subscription) {
            $this->addFlash('info', 'Your team does not have an active subscription.');
            return $this->redirectToRoute('subscription_plans');
        }

        $stats = $this->subscriptionManager->getSubscriptionStats($team);

        return $this->render('subscription/current.html.twig', [
            'team' => $team,
            'subscription' => $subscription,
            'stats' => $stats,
        ]);
    }

    #[Route('/subscribe/{planSlug}', name: 'subscription_subscribe')]
    public function subscribe(string $planSlug, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $teams = $user->getTeamMembers();
        if ($teams->isEmpty()) {
            $this->addFlash('error', 'You must be part of a team to subscribe.');
            return $this->redirectToRoute('subscription_plans');
        }

        $team = $teams->first()->getTeam();

        // Check if user is owner or admin
        if (!$team->isOwner($user)) {
            $member = $team->getMemberByUser($user);
            if (!$member || !in_array($member->getRole(), ['admin', 'owner'])) {
                $this->addFlash('error', 'Only team owners and admins can manage subscriptions.');
                return $this->redirectToRoute('subscription_current');
            }
        }

        $plan = $this->planRepository->findBySlug($planSlug);
        if (!$plan) {
            throw $this->createNotFoundException('Plan not found');
        }

        if ($request->isMethod('POST')) {
            try {
                $withTrial = $request->request->get('with_trial', false);
                $subscription = $this->subscriptionManager->createSubscription($team, $plan, $withTrial);

                $this->addFlash('success', 'Successfully subscribed to ' . $plan->getName() . '!');
                return $this->redirectToRoute('subscription_current');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('subscription/subscribe.html.twig', [
            'team' => $team,
            'plan' => $plan,
        ]);
    }

    #[Route('/upgrade', name: 'subscription_upgrade')]
    public function upgrade(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $teams = $user->getTeamMembers();
        if ($teams->isEmpty()) {
            $this->addFlash('error', 'You must be part of a team.');
            return $this->redirectToRoute('subscription_plans');
        }

        $team = $teams->first()->getTeam();
        $currentSubscription = $team->getSubscription();

        if (!$currentSubscription) {
            $this->addFlash('error', 'No active subscription found.');
            return $this->redirectToRoute('subscription_plans');
        }

        // Check permissions
        $this->denyAccessUnlessGranted('SUBSCRIPTION_UPGRADE', $currentSubscription);

        if ($request->isMethod('POST')) {
            $newPlanId = $request->request->get('plan_id');
            $newPlan = $this->planRepository->find($newPlanId);

            if (!$newPlan) {
                $this->addFlash('error', 'Invalid plan selected.');
                return $this->redirectToRoute('subscription_upgrade');
            }

            try {
                $this->subscriptionManager->changePlan($currentSubscription, $newPlan);
                $this->addFlash('success', 'Successfully changed to ' . $newPlan->getName() . '!');
                return $this->redirectToRoute('subscription_current');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $availablePlans = $this->planManager->getActivePlans();

        return $this->render('subscription/upgrade.html.twig', [
            'team' => $team,
            'currentSubscription' => $currentSubscription,
            'availablePlans' => $availablePlans,
        ]);
    }

    #[Route('/cancel', name: 'subscription_cancel')]
    public function cancel(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $teams = $user->getTeamMembers();
        if ($teams->isEmpty()) {
            $this->addFlash('error', 'You must be part of a team.');
            return $this->redirectToRoute('subscription_plans');
        }

        $team = $teams->first()->getTeam();
        $subscription = $team->getSubscription();

        if (!$subscription) {
            $this->addFlash('error', 'No active subscription found.');
            return $this->redirectToRoute('subscription_plans');
        }

        // Check permissions - only owner can cancel
        $this->denyAccessUnlessGranted('SUBSCRIPTION_CANCEL', $subscription);

        if ($request->isMethod('POST')) {
            $immediately = $request->request->get('immediately', false);
            
            try {
                $this->subscriptionManager->cancelSubscription($subscription, $immediately);
                
                if ($immediately) {
                    $this->addFlash('success', 'Subscription canceled immediately.');
                } else {
                    $this->addFlash('success', 'Subscription will be canceled at the end of the current billing period.');
                }
                
                return $this->redirectToRoute('subscription_current');
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('subscription/cancel.html.twig', [
            'team' => $team,
            'subscription' => $subscription,
        ]);
    }

    #[Route('/resume', name: 'subscription_resume')]
    public function resume(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        $teams = $user->getTeamMembers();
        if ($teams->isEmpty()) {
            $this->addFlash('error', 'You must be part of a team.');
            return $this->redirectToRoute('subscription_plans');
        }

        $team = $teams->first()->getTeam();
        $subscription = $team->getSubscription();

        if (!$subscription) {
            $this->addFlash('error', 'No subscription found.');
            return $this->redirectToRoute('subscription_plans');
        }

        // Check permissions
        $this->denyAccessUnlessGranted('SUBSCRIPTION_MANAGE', $subscription);

        try {
            $this->subscriptionManager->resumeSubscription($subscription);
            $this->addFlash('success', 'Subscription resumed successfully!');
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('subscription_current');
    }
}
