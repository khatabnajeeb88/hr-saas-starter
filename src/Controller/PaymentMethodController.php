<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use App\Service\TapPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment-method')]
#[IsGranted('ROLE_USER')]
class PaymentMethodController extends AbstractController
{
    #[Route('/', name: 'payment_method_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get user's active team subscription
        $subscription = null;
        foreach ($user->getTeamMembers() as $member) {
            $team = $member->getTeam();
            if ($team->getSubscription() && $team->getSubscription()->getStatus() === Subscription::STATUS_ACTIVE) {
                $subscription = $team->getSubscription();
                break;
            }
        }

        return $this->render('payment_method/index.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/update', name: 'payment_method_update', methods: ['GET', 'POST'])]
    public function update(
        Request $request,
        TapPaymentService $tapPaymentService,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get user's active team subscription
        $subscription = null;
        foreach ($user->getTeamMembers() as $member) {
            $team = $member->getTeam();
            if ($team->getSubscription() && $team->getSubscription()->getStatus() === Subscription::STATUS_ACTIVE) {
                $subscription = $team->getSubscription();
                break;
            }
        }

        if (!$subscription) {
            $this->addFlash('error', 'No active subscription found.');
            return $this->redirectToRoute('subscription_plans');
        }

        if ($request->isMethod('POST')) {
            $cardNumber = $request->request->get('card_number');
            $expiryMonth = $request->request->get('expiry_month');
            $expiryYear = $request->request->get('expiry_year');
            $cvv = $request->request->get('cvv');
            $cardholderName = $request->request->get('cardholder_name');

            // Basic validation
            if (empty($cardNumber) || empty($expiryMonth) || empty($expiryYear) || empty($cvv)) {
                $this->addFlash('error', 'All card fields are required.');
                return $this->render('payment_method/update.html.twig', [
                    'subscription' => $subscription,
                ]);
            }

            try {
                // Create a test charge to validate the card and get payment method ID
                // In production, you'd use Tap's card tokenization
                $charge = $tapPaymentService->createCharge(
                    1.00, // Minimal amount for validation
                    $subscription->getPlan()->getCurrency(),
                    'Payment method validation',
                    [
                        'number' => $cardNumber,
                        'exp_month' => $expiryMonth,
                        'exp_year' => $expiryYear,
                        'cvc' => $cvv,
                        'name' => $cardholderName,
                    ]
                );

                // Update subscription with new payment method
                if (isset($charge['card']['id'])) {
                    $subscription->setPaymentMethodId($charge['card']['id']);
                    $entityManager->flush();

                    $this->addFlash('success', 'Payment method updated successfully.');
                    return $this->redirectToRoute('payment_method_index');
                }

                $this->addFlash('error', 'Failed to update payment method.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        return $this->render('payment_method/update.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/remove', name: 'payment_method_remove', methods: ['POST'])]
    public function remove(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get user's active team subscription
        $subscription = null;
        foreach ($user->getTeamMembers() as $member) {
            $team = $member->getTeam();
            if ($team->getSubscription() && $team->getSubscription()->getStatus() === Subscription::STATUS_ACTIVE) {
                $subscription = $team->getSubscription();
                break;
            }
        }

        if (!$subscription) {
            $this->addFlash('error', 'No active subscription found.');
            return $this->redirectToRoute('subscription_plans');
        }

        $subscription->setPaymentMethodId(null);
        $entityManager->flush();

        $this->addFlash('success', 'Payment method removed successfully.');
        
        return $this->redirectToRoute('payment_method_index');
    }
}
