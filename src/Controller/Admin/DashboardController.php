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
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

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
        $locale = $this->requestStack->getCurrentRequest()->getLocale();

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Users & Teams');
        yield MenuItem::linkToRoute('Users', 'fas fa-users', 'admin_user_index', ['_locale' => $locale]);
        yield MenuItem::linkToRoute('Team Invites', 'fas fa-envelope', 'admin_team_invitation_index', ['_locale' => $locale]);
        yield MenuItem::linkToRoute('Social Providers', 'fas fa-share-alt', 'admin_social_provider_index', ['_locale' => $locale]);
        
        yield MenuItem::section('Billing');
        yield MenuItem::linkToRoute('Plans', 'fas fa-tags', 'admin_subscription_plan_index', ['_locale' => $locale]);
        yield MenuItem::linkToRoute('Subscriptions', 'fas fa-file-contract', 'admin_subscription_index', ['_locale' => $locale]);
        yield MenuItem::linkToRoute('Invoices', 'fas fa-file-invoice-dollar', 'admin_invoice_index', ['_locale' => $locale]);
        yield MenuItem::linkToRoute('Payments', 'fas fa-money-bill-wave', 'admin_payment_index', ['_locale' => $locale]);
        
        yield MenuItem::section('System');
        yield MenuItem::linkToUrl('Return to App', 'fas fa-arrow-left', '/');
    }
}
