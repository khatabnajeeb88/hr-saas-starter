<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Find active subscription for a team
     */
    public function findActiveByTeam(Team $team): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.team = :team')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('team', $team)
            ->setParameter('statuses', [Subscription::STATUS_TRIAL, Subscription::STATUS_ACTIVE])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find subscriptions expiring soon (within specified days)
     *
     * @return Subscription[]
     */
    public function findExpiringSubscriptions(int $days = 7): array
    {
        $futureDate = new \DateTimeImmutable("+{$days} days");
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('s')
            ->where('s.currentPeriodEnd BETWEEN :now AND :future')
            ->andWhere('s.status = :status')
            ->andWhere('s.autoRenew = :autoRenew')
            ->setParameter('now', $now)
            ->setParameter('future', $futureDate)
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('autoRenew', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find subscriptions by status
     *
     * @return Subscription[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find subscriptions with expired trials
     *
     * @return Subscription[]
     */
    public function findExpiredTrials(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.trialEndsAt <= :now')
            ->setParameter('status', Subscription::STATUS_TRIAL)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all subscriptions for a specific plan
     *
     * @return Subscription[]
     */
    public function findByPlanId(int $planId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.plan = :planId')
            ->setParameter('planId', $planId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
