<?php

namespace App\Command;

use App\Repository\SubscriptionRepository;
use App\Service\PaymentNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:subscription:send-reminders',
    description: 'Send trial ending and renewal reminder emails',
)]
class SubscriptionReminderCommand extends Command
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private PaymentNotificationService $notificationService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Sending Subscription Reminders');

        $trialCount = $this->sendTrialEndingReminders($io);
        $renewalCount = $this->sendRenewalReminders($io);

        $io->success(sprintf(
            'Sent %d trial ending reminders and %d renewal reminders',
            $trialCount,
            $renewalCount
        ));

        return Command::SUCCESS;
    }

    private function sendTrialEndingReminders(SymfonyStyle $io): int
    {
        $io->section('Trial Ending Reminders');

        // Find trials ending in 3 days
        $threeDaysFromNow = new \DateTimeImmutable('+3 days');
        $fourDaysFromNow = new \DateTimeImmutable('+4 days');

        $subscriptions = $this->subscriptionRepository->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.trialEndsAt >= :start')
            ->andWhere('s.trialEndsAt < :end')
            ->setParameter('status', 'trial')
            ->setParameter('start', $threeDaysFromNow->format('Y-m-d 00:00:00'))
            ->setParameter('end', $fourDaysFromNow->format('Y-m-d 00:00:00'))
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($subscriptions as $subscription) {
            try {
                $this->notificationService->sendTrialEndingEmail($subscription);
                $io->text(sprintf(
                    'Sent trial ending email for subscription #%d (%s)',
                    $subscription->getId(),
                    $subscription->getTeam()->getName()
                ));
                $count++;
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Failed to send trial ending email for subscription #%d: %s',
                    $subscription->getId(),
                    $e->getMessage()
                ));
            }
        }

        return $count;
    }

    private function sendRenewalReminders(SymfonyStyle $io): int
    {
        $io->section('Renewal Reminders');

        // Find subscriptions renewing in 7 days
        $sevenDaysFromNow = new \DateTimeImmutable('+7 days');
        $eightDaysFromNow = new \DateTimeImmutable('+8 days');

        $subscriptions = $this->subscriptionRepository->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.autoRenew = :autoRenew')
            ->andWhere('s.nextBillingDate >= :start')
            ->andWhere('s.nextBillingDate < :end')
            ->setParameter('status', 'active')
            ->setParameter('autoRenew', true)
            ->setParameter('start', $sevenDaysFromNow->format('Y-m-d'))
            ->setParameter('end', $eightDaysFromNow->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($subscriptions as $subscription) {
            try {
                $this->notificationService->sendRenewalReminderEmail($subscription);
                $io->text(sprintf(
                    'Sent renewal reminder for subscription #%d (%s)',
                    $subscription->getId(),
                    $subscription->getTeam()->getName()
                ));
                $count++;
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Failed to send renewal reminder for subscription #%d: %s',
                    $subscription->getId(),
                    $e->getMessage()
                ));
            }
        }

        return $count;
    }
}
