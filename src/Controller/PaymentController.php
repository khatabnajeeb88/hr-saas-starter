<?php

namespace App\Controller;

use App\Repository\SubscriptionRepository;
use App\Service\TapPaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    public function __construct(
        private TapPaymentService $tapPaymentService, // Keep for BC or migrate logic
        private SubscriptionRepository $subscriptionRepository,
        private UrlGeneratorInterface $urlGenerator,
        private \App\Service\PaymentGatewayFactory $gatewayFactory,
    ) {
    }

    #[Route('/checkout/{id}', name: 'payment_checkout')]
    public function checkout(Request $request, int $id): Response
    {
        $subscription = $this->subscriptionRepository->find($id);

        if (!$subscription) {
            throw $this->createNotFoundException('Subscription not found');
        }

        // Check permissions
        $this->denyAccessUnlessGranted('SUBSCRIPTION_MANAGE', $subscription);

        // Determine gateway
        $gatewayName = $request->query->get('gateway');
        
        try {
            if ($gatewayName) {
                $gateway = $this->gatewayFactory->getGateway($gatewayName);
            } else {
                $gateway = $this->gatewayFactory->getDefaultGateway();
                $gatewayName = $gateway->getName();
            }
            
            // Generate URLs
            $redirectUrl = $this->urlGenerator->generate('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $webhookUrl = $this->urlGenerator->generate('webhook_' . $gatewayName, [], UrlGeneratorInterface::ABSOLUTE_URL);
            
            $chargeData = $gateway->createCharge(
                $subscription,
                [
                    'redirect_url' => $redirectUrl,
                    'webhook_url' => $webhookUrl,
                    'source_id' => null, // Or handle saved cards
                ]
            );

            // Handle response based on gateway
            // Tap returns transaction.url
            // Stripe returns transaction.url (mapped in adapter) or url directly
            
            if (isset($chargeData['transaction']['url'])) {
                return $this->redirect($chargeData['transaction']['url']);
            } elseif (isset($chargeData['url'])) {
                return $this->redirect($chargeData['url']);
            }

            $this->addFlash('error', 'Failed to initialize payment. Please try again.');
            return $this->redirectToRoute('subscription_current');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Payment error: ' . $e->getMessage());
            return $this->redirectToRoute('subscription_current');
        }
    }

    #[Route('/success', name: 'payment_success')]
    public function success(Request $request): Response
    {
        // Handle Tap
        $tapId = $request->query->get('tap_id');
        
        // Handle Stripe
        $stripeSessionId = $request->query->get('session_id');
        $gatewayName = $request->query->get('gateway'); // We added this in Stripe return_url

        $chargeData = null;

        try {
            if ($tapId) {
                $chargeData = $this->gatewayFactory->getGateway('tap')->retrieveCharge($tapId);
            } elseif ($stripeSessionId && $gatewayName === 'stripe') {
                $chargeData = $this->gatewayFactory->getGateway('stripe')->retrieveCharge($stripeSessionId);
            }
        } catch (\Exception $e) {
            // Log error
        }

        return $this->render('payment/success.html.twig', [
            'charge' => $chargeData,
        ]);
    }

    #[Route('/failure', name: 'payment_failure')]
    public function failure(Request $request): Response
    {
        $tapId = $request->query->get('tap_id');
        $errorMessage = null;

        if ($tapId) {
            try {
                // We can use legacy service or new gateway
                $chargeData = $this->tapPaymentService->retrieveCharge($tapId);
                $errorMessage = $chargeData['response']['message'] ?? 'Payment failed';
            } catch (\Exception $e) {
                $errorMessage = 'Payment processing error';
            }
        }

        return $this->render('payment/failure.html.twig', [
            'error_message' => $errorMessage,
        ]);
    }

    #[Route('/methods', name: 'payment_methods')]
    public function methods(): Response
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

        return $this->render('payment/methods.html.twig', [
            'team' => $team,
            'subscription' => $subscription,
        ]);
    }
}
