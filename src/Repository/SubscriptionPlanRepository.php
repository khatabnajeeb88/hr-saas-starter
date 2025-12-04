<?php

namespace App\Repository;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionPlan>
 */
class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    /**
     * Find all active plans ordered by display order
     *
     * @return SubscriptionPlan[]
     */
    public function findActivePlans(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a plan by slug
     */
    public function findBySlug(string $slug): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find the default free plan
     */
    public function findDefaultPlan(): ?SubscriptionPlan
    {
        return $this->findBySlug('free');
    }

    /**
     * Find plans by billing interval
     *
     * @return SubscriptionPlan[]
     */
    public function findByBillingInterval(string $interval): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.billingInterval = :interval')
            ->andWhere('p.isActive = :active')
            ->setParameter('interval', $interval)
            ->setParameter('active', true)
            ->orderBy('p.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
