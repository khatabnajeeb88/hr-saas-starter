<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(InvoiceRepository $invoiceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get user's teams and subscriptions
        $teams = [];
        $activeSubscription = null;
        $recentInvoices = [];
        
        foreach ($user->getTeamMembers() as $teamMember) {
            $team = $teamMember->getTeam();
            $teams[] = [
                'name' => $team->getName(),
                'role' => $teamMember->getRole(),
                'memberCount' => $team->getMembers()->count(),
            ];
            
            if ($team->getSubscription()) {
                $subscription = $team->getSubscription();
                if ($subscription->getStatus() === 'active' || $subscription->getStatus() === 'trial') {
                    $activeSubscription = $subscription;
                    
                    // Get recent invoices for this subscription
                    $invoices = $invoiceRepository->findBySubscription($subscription);
                    $recentInvoices = array_merge($recentInvoices, array_slice($invoices, 0, 3));
                }
            }
        }
        
        // Sort invoices by date
        usort($recentInvoices, function ($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        $recentInvoices = array_slice($recentInvoices, 0, 5);

        return $this->render('dashboard/index.html.twig', [
            'teams' => $teams,
            'activeSubscription' => $activeSubscription,
            'recentInvoices' => $recentInvoices,
        ]);
    }
}
