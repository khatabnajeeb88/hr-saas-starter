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
use Symfony\Component\Uid\Uuid;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
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

    private const TEMPLATES = [
        'payment_success' => 'Payment Success',
        'payment_failure' => 'Payment Failure',
        'trial_ending' => 'Trial Ending',
        'renewal_reminder' => 'Renewal Reminder',
        'dunning_notice' => 'Dunning Notice',
        'subscription_suspended' => 'Subscription Suspended',
        'team_invitation' => 'Team Invitation',
    ];

    #[Route('/admin/email-preview', name: 'admin_email_preview_index')]
    public function emailPreviewIndex(): Response
    {
        return $this->render('admin/email_preview/index.html.twig', [
            'templates' => self::TEMPLATES,
        ]);
    }

    #[Route('/admin/email-preview/{template}', name: 'admin_email_preview_show')]
    public function emailPreviewShow(string $template): Response
    {
        if (!array_key_exists($template, self::TEMPLATES)) {
            throw $this->createNotFoundException('Template not found');
        }

        $context = $this->createContext($template);

        return $this->render("email/{$template}.html.twig", $context);
    }

    private function createContext(string $template): array
    {
        $user = new User();
        $user->setEmail('[email protected]')
            ->setName('John Doe');

        $plan = new SubscriptionPlan();
        $plan->setName('Pro Plan')
            ->setPrice(29.00)
            ->setBillingInterval('month');

        $team = new Team();
        $team->setName('Acme Corp')
            ->setOwner($user);

        $subscription = new Subscription();
        $subscription->setTeam($team)
            ->setPlan($plan)
            ->setStatus(Subscription::STATUS_ACTIVE)
            ->setNextBillingDate(new \DateTimeImmutable('+1 month'))
            ->setCurrentPeriodEnd(new \DateTimeImmutable('+1 month'))
            ->setTrialEndsAt(new \DateTimeImmutable('+14 days'))
            ->setGateway('tap');

        $payment = new Payment();
        $payment->setAmount('29.00')
            ->setCurrency('USD')
            ->setChargeId('ch_1234567890')
            ->setStatus(Payment::STATUS_CAPTURED)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setSubscription($subscription)
            ->setCardBrand('Visa')
            ->setCardLastFour('4242');

        $commonContext = [
            'user' => $user,
            'team' => $team,
            'subscription' => $subscription,
        ];

        switch ($template) {
            case 'payment_success':
                return array_merge($commonContext, [
                    'payment' => $payment,
                ]);

            case 'payment_failure':
                $payment->setStatus(Payment::STATUS_FAILED);
                return array_merge($commonContext, [
                    'payment' => $payment,
                    'retry_count' => 1,
                ]);

            case 'trial_ending':
                return array_merge($commonContext, [
                    'days_remaining' => 3,
                ]);

            case 'renewal_reminder':
                return array_merge($commonContext, [
                    'days_until_renewal' => 7,
                ]);

            case 'dunning_notice':
                return array_merge($commonContext, [
                    'retry_count' => 2,
                    'retries_remaining' => 1,
                    'grace_period_ends_at' => new \DateTimeImmutable('+3 days'),
                ]);

            case 'subscription_suspended':
                $subscription->setStatus(Subscription::STATUS_EXPIRED);
                return $commonContext;

            case 'team_invitation':
                $invitation = new TeamInvitation();
                $invitation->setEmail('[email protected]')
                    ->setTeam($team)
                    ->setToken(Uuid::v4()->toBase58());
                
                return [
                    'invitation' => $invitation,
                    'team' => $team,
                ];

            default:
                return [];
        }
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
        yield MenuItem::linkToUrl('Audit Logs', 'fas fa-list-alt', $this->generateUrl('admin_audit_log_index', ['_locale' => $locale]));
        yield MenuItem::linkToCrud('Announcements', 'fas fa-bullhorn', \App\Entity\Announcement::class);
        yield MenuItem::linkToUrl('Email Previews', 'fas fa-envelope-open-text', $this->generateUrl('admin_email_preview_index', ['_locale' => $locale]));
        yield MenuItem::linkToUrl('Return to App', 'fas fa-arrow-left', '/');
    }
}
