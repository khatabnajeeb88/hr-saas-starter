<?php

namespace App\Command;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use App\Service\SubscriptionManager;
use App\Service\TapPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:payment:process-recurring',
    description: 'Process recurring payments for active subscriptions',
)]
class RecurringPaymentCommand extends Command
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionManager $subscriptionManager,
        private \App\Service\PaymentGatewayFactory $gatewayFactory, // Use factory
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }
    
    // ... (configure remains same)

    private function processSubscriptionRenewal(Subscription $subscription, bool $dryRun, SymfonyStyle $io): string
    {
        // Check if subscription has a saved payment method
        if (!$subscription->getPaymentMethodId()) {
            $io->warning('No saved payment method - skipping');
            $this->logger->warning('No payment method for renewal', [
                'subscription_id' => $subscription->getId(),
            ]);
            return 'skipped';
        }

        $plan = $subscription->getPlan();
        $io->text(sprintf('Plan: %s - %s', $plan->getName(), $plan->getFormattedPrice()));

        if ($dryRun) {
            $io->text('[DRY RUN] Would charge payment method');
            return 'skipped';
        }

        try {
            // Generate webhook URL
            $gatewayName = $subscription->getGateway() ?? 'tap'; // Default to tap
            $webhookUrl = $this->urlGenerator->generate('webhook_' . $gatewayName, [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Get gateway
            $gateway = $this->gatewayFactory->getGateway($gatewayName);

            // Charge the saved payment method
            $io->text(sprintf('Charging saved payment method via %s...', $gatewayName));
            
            $chargeData = $gateway->chargeSavedPaymentMethod($subscription, [
                'webhook_url' => $webhookUrl
            ]);

            // Check if charge was successful
            // Gateways return 'status' key (CAPTURED, PENDING, FAILED)
            $status = strtoupper($chargeData['status'] ?? '');
            
            if ($status === 'CAPTURED') {
                $io->success('Payment successful!');
                
                // Update subscription
                $this->handleSuccessfulRenewal($subscription);
                
                $this->logger->info('Recurring payment successful', [
                    'subscription_id' => $subscription->getId(),
                    'charge_id' => $chargeData['id'] ?? null,
                    'gateway' => $gatewayName,
                ]);

                return 'success';
            } else {
                $io->error(sprintf('Payment failed or pending (Status: %s)', $status));
                
                // Handle failed payment
                $this->handleFailedRenewal($subscription);
                
                $this->logger->warning('Recurring payment failed', [
                    'subscription_id' => $subscription->getId(),
                    'status' => $status,
                    'gateway' => $gatewayName,
                ]);

                return 'failed';
            }
        } catch (\Exception $e) {
            $io->error('Exception: ' . $e->getMessage());
            
            // Handle failed payment
            $this->handleFailedRenewal($subscription);
            
            throw $e;
        }
    }

    private function handleSuccessfulRenewal(Subscription $subscription): void
    {
        // Reset retry count
        $subscription->resetRetryCount();
        
        // Clear grace period
        $subscription->setGracePeriodEndsAt(null);
        
        // Update status to active if it was past_due
        if ($subscription->getStatus() === Subscription::STATUS_PAST_DUE) {
            $subscription->setStatus(Subscription::STATUS_ACTIVE);
        }
        
        // Update last payment date
        $subscription->setLastPaymentAt(new \DateTimeImmutable());
        
        // Calculate next billing date
        $nextBillingDate = $this->calculateNextBillingDate($subscription);
        $subscription->setNextBillingDate($nextBillingDate);
        
        // Extend current period
        $subscription->setCurrentPeriodStart(new \DateTimeImmutable());
        $subscription->setCurrentPeriodEnd($nextBillingDate);
        
        $this->entityManager->flush();
    }

    private function handleFailedRenewal(Subscription $subscription): void
    {
        // Increment retry count
        $subscription->incrementRetryCount();
        
        // Mark as past_due
        $subscription->setStatus(Subscription::STATUS_PAST_DUE);
        
        // Set grace period (10 days from now)
        $gracePeriod = new \DateTimeImmutable('+10 days');
        $subscription->setGracePeriodEndsAt($gracePeriod);
        
        // Schedule next retry based on retry count
        $retryDays = $this->getRetrySchedule($subscription->getRetryCount());
        if ($retryDays !== null) {
            $nextRetry = new \DateTimeImmutable("+{$retryDays} days");
            $subscription->setNextBillingDate($nextRetry);
        } else {
            // Max retries reached - suspend subscription
            $subscription->setStatus(Subscription::STATUS_CANCELED);
            $subscription->setEndsAt(new \DateTimeImmutable());
            $subscription->setCanceledAt(new \DateTimeImmutable());
            
            $this->logger->warning('Subscription suspended after max retries', [
                'subscription_id' => $subscription->getId(),
            ]);
        }
        
        $this->entityManager->flush();
    }

    private function calculateNextBillingDate(Subscription $subscription): \DateTimeImmutable
    {
        $plan = $subscription->getPlan();
        $interval = $plan->getBillingInterval();
        
        return match ($interval) {
            'monthly' => new \DateTimeImmutable('+1 month'),
            'yearly' => new \DateTimeImmutable('+1 year'),
            default => new \DateTimeImmutable('+1 month'),
        };
    }

    private function getRetrySchedule(int $retryCount): ?int
    {
        // Retry schedule: 0 days (immediate), 3 days, 7 days
        $schedule = [0, 3, 7];
        
        return $schedule[$retryCount] ?? null;
    }
}
