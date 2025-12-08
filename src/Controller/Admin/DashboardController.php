<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use App\Entity\Payment;
use App\Entity\SocialProvider;
use App\Entity\Subscription;
use App\Entity\SubscriptionPlan;
use App\Entity\Team;
use App\Entity\TeamInvitation;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SaaS Admin');
    }

    public function configureUserMenu(\Symfony\Component\Security\Core\User\UserInterface $user): \EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu
    {
        return parent::configureUserMenu($user)
            ->addMenuItems([
                MenuItem::linkToUrl('English', 'fa fa-language', $this->generateUrl('admin', ['_locale' => 'en'])),
                MenuItem::linkToUrl('Arabic', 'fa fa-language', $this->generateUrl('admin', ['_locale' => 'ar'])),
            ]);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Users & Teams');
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Team Invites', 'fas fa-envelope', TeamInvitation::class);
        yield MenuItem::linkToCrud('Social Providers', 'fas fa-share-alt', SocialProvider::class);
        
        yield MenuItem::section('Billing');
        yield MenuItem::linkToCrud('Plans', 'fas fa-tags', SubscriptionPlan::class);
        yield MenuItem::linkToCrud('Subscriptions', 'fas fa-file-contract', Subscription::class);
        yield MenuItem::linkToCrud('Invoices', 'fas fa-file-invoice-dollar', Invoice::class);
        yield MenuItem::linkToCrud('Payments', 'fas fa-money-bill-wave', Payment::class);
        
        yield MenuItem::section('System');
        yield MenuItem::linkToUrl('Return to App', 'fas fa-arrow-left', '/');
    }
}
