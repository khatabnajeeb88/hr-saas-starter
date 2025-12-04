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
        private TapPaymentService $tapPaymentService,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without actually charging payments')
            ->addOption('subscription-id', null, InputOption::VALUE_REQUIRED, 'Process specific subscription ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $subscriptionId = $input->getOption('subscription-id');

        $io->title('Processing Recurring Payments');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No charges will be processed');
        }

        // Get subscriptions due for renewal
        $subscriptions = $this->getSubscriptionsDueForRenewal($subscriptionId);

        if (empty($subscriptions)) {
            $io->success('No subscriptions due for renewal');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d subscription(s) due for renewal', count($subscriptions)));

        $successCount = 0;
        $failureCount = 0;
        $skippedCount = 0;

        foreach ($subscriptions as $subscription) {
            $io->section(sprintf(
                'Processing subscription #%d for team: %s',
                $subscription->getId(),
                $subscription->getTeam()->getName()
            ));

            try {
                $result = $this->processSubscriptionRenewal($subscription, $dryRun, $io);

                if ($result === 'success') {
                    $successCount++;
                } elseif ($result === 'failed') {
                    $failureCount++;
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $io->error(sprintf('Error processing subscription #%d: %s', $subscription->getId(), $e->getMessage()));
                $this->logger->error('Recurring payment error', [
                    'subscription_id' => $subscription->getId(),
                    'error' => $e->getMessage(),
                ]);
                $failureCount++;
            }
        }

        $io->success(sprintf(
            'Processed %d subscriptions: %d successful, %d failed, %d skipped',
            count($subscriptions),
            $successCount,
            $failureCount,
            $skippedCount
        ));

        return Command::SUCCESS;
    }

    private function getSubscriptionsDueForRenewal(?int $subscriptionId = null): array
    {
        if ($subscriptionId) {
            $subscription = $this->subscriptionRepository->find($subscriptionId);
            return $subscription ? [$subscription] : [];
        }

        $today = new \DateTimeImmutable('today');

        return $this->subscriptionRepository->createQueryBuilder('s')
            ->where('s.status IN (:statuses)')
            ->andWhere('s.nextBillingDate <= :today')
            ->andWhere('s.autoRenew = :autoRenew')
            ->setParameter('statuses', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PAST_DUE])
            ->setParameter('today', $today)
            ->setParameter('autoRenew', true)
            ->getQuery()
            ->getResult();
    }

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
            $webhookUrl = $this->urlGenerator->generate('webhook_tap', [], UrlGeneratorInterface::ABSOLUTE_URL);

            // Charge the saved payment method
            $io->text('Charging saved payment method...');
            $chargeData = $this->tapPaymentService->chargePaymentMethod($subscription, $webhookUrl);

            // Check if charge was successful
            if (isset($chargeData['status']) && strtoupper($chargeData['status']) === 'CAPTURED') {
                $io->success('Payment successful!');
                
                // Update subscription
                $this->handleSuccessfulRenewal($subscription);
                
                $this->logger->info('Recurring payment successful', [
                    'subscription_id' => $subscription->getId(),
                    'charge_id' => $chargeData['id'] ?? null,
                ]);

                return 'success';
            } else {
                $io->error('Payment failed or pending');
                
                // Handle failed payment
                $this->handleFailedRenewal($subscription);
                
                $this->logger->warning('Recurring payment failed', [
                    'subscription_id' => $subscription->getId(),
                    'status' => $chargeData['status'] ?? 'unknown',
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
