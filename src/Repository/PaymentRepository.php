<?php

namespace App\Repository;

use App\Entity\Payment;
use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Find payment by charge ID
     */
    public function findByChargeId(string $chargeId): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->where('p.chargeId = :chargeId')
            ->setParameter('chargeId', $chargeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all payments for a subscription
     *
     * @return Payment[]
     */
    public function findBySubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find successful payments for a subscription
     *
     * @return Payment[]
     */
    public function findSuccessfulBySubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.subscription = :subscription')
            ->andWhere('p.status = :status')
            ->setParameter('subscription', $subscription)
            ->setParameter('status', Payment::STATUS_CAPTURED)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find failed payments for a subscription
     *
     * @return Payment[]
     */
    public function findFailedBySubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.subscription = :subscription')
            ->andWhere('p.status = :status')
            ->setParameter('subscription', $subscription)
            ->setParameter('status', Payment::STATUS_FAILED)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total revenue from successful payments
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount) as total')
            ->where('p.status = :status')
            ->setParameter('status', Payment::STATUS_CAPTURED)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Get payments within date range
     *
     * @return Payment[]
     */
    public function findByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
