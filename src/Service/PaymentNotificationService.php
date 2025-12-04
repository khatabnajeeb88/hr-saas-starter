<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Subscription;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class PaymentNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail = '[email protected]',
        private string $fromName = 'SaaS Starter Pack',
    ) {
    }

    /**
     * Send payment success email with receipt
     */
    public function sendPaymentSuccessEmail(Payment $payment): void
    {
        $subscription = $payment->getSubscription();
        $team = $subscription->getTeam();
        $owner = $team->getOwner();

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($owner->getEmail()))
                ->subject('Payment Successful - Receipt')
                ->htmlTemplate('email/payment_success.html.twig')
                ->context([
                    'payment' => $payment,
                    'subscription' => $subscription,
                    'team' => $team,
                    'user' => $owner,
                ]);

            $this->mailer->send($email);

            $this->logger->info('Payment success email sent', [
                'payment_id' => $payment->getId(),
                'email' => $owner->getEmail(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send payment success email', [
                'payment_id' => $payment->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send payment failure email
     */
    public function sendPaymentFailureEmail(Payment $payment): void
    {
        $subscription = $payment->getSubscription();
        $team = $subscription->getTeam();
        $owner = $team->getOwner();

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($owner->getEmail()))
                ->subject('Payment Failed - Action Required')
                ->htmlTemplate('email/payment_failure.html.twig')
                ->context([
                    'payment' => $payment,
                    'subscription' => $subscription,
                    'team' => $team,
                    'user' => $owner,
                    'retry_count' => $subscription->getRetryCount(),
                ]);

            $this->mailer->send($email);

            $this->logger->info('Payment failure email sent', [
                'payment_id' => $payment->getId(),
                'email' => $owner->getEmail(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send payment failure email', [
                'payment_id' => $payment->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send trial ending reminder
     */
    public function sendTrialEndingEmail(Subscription $subscription): void
    {
        $team = $subscription->getTeam();
        $owner = $team->getOwner();

        try {
            $daysRemaining = $subscription->daysRemaining();

            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($owner->getEmail()))
                ->subject(sprintf('Your trial ends in %d days', $daysRemaining))
                ->htmlTemplate('email/trial_ending.html.twig')
                ->context([
                    'subscription' => $subscription,
                    'team' => $team,
                    'user' => $owner,
                    'days_remaining' => $daysRemaining,
                ]);

            $this->mailer->send($email);

            $this->logger->info('Trial ending email sent', [
                'subscription_id' => $subscription->getId(),
                'email' => $owner->getEmail(),
                'days_remaining' => $daysRemaining,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send trial ending email', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send renewal reminder
     */
    public function sendRenewalReminderEmail(Subscription $subscription): void
    {
        $team = $subscription->getTeam();
        $owner = $team->getOwner();

        try {
            $daysUntilRenewal = $subscription->daysRemaining();

            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($owner->getEmail()))
                ->subject('Upcoming Subscription Renewal')
                ->htmlTemplate('email/renewal_reminder.html.twig')
                ->context([
                    'subscription' => $subscription,
                    'team' => $team,
                    'user' => $owner,
                    'days_until_renewal' => $daysUntilRenewal,
                ]);

            $this->mailer->send($email);

            $this->logger->info('Renewal reminder email sent', [
                'subscription_id' => $subscription->getId(),
                'email' => $owner->getEmail(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send renewal reminder email', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send dunning notice for failed payment
     */
    public function sendDunningEmail(Subscription $subscription): void
    {
        $team = $subscription->getTeam();
        $owner = $team->getOwner();

        try {
            $retryCount = $subscription->getRetryCount();
            $maxRetries = 3;
            $retriesRemaining = $maxRetries - $retryCount;

            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($owner->getEmail()))
                ->subject('Payment Failed - Update Payment Method')
                ->htmlTemplate('email/dunning_notice.html.twig')
                ->context([
                    'subscription' => $subscription,
                    'team' => $team,
                    'user' => $owner,
                    'retry_count' => $retryCount,
                    'retries_remaining' => $retriesRemaining,
                    'grace_period_ends_at' => $subscription->getGracePeriodEndsAt(),
                ]);

            $this->mailer->send($email);

            $this->logger->info('Dunning email sent', [
                'subscription_id' => $subscription->getId(),
                'email' => $owner->getEmail(),
                'retry_count' => $retryCount,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send dunning email', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send subscription suspended email
     */
    public function sendSubscriptionSuspendedEmail(Subscription $subscription): void
    {
        $team = $subscription->getTeam();
        $owner = $team->getOwner();

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, $this->fromName))
                ->to(new Address($owner->getEmail()))
                ->subject('Subscription Suspended - Action Required')
                ->htmlTemplate('email/subscription_suspended.html.twig')
                ->context([
                    'subscription' => $subscription,
                    'team' => $team,
                    'user' => $owner,
                ]);

            $this->mailer->send($email);

            $this->logger->info('Subscription suspended email sent', [
                'subscription_id' => $subscription->getId(),
                'email' => $owner->getEmail(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send subscription suspended email', [
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
