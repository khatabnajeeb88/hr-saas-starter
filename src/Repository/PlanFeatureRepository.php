<?php

namespace App\Repository;

use App\Entity\PlanFeature;
use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanFeature>
 */
class PlanFeatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanFeature::class);
    }

    /**
     * Find all features for a specific plan
     *
     * @return PlanFeature[]
     */
    public function findByPlan(SubscriptionPlan $plan): array
    {
        return $this->createQueryBuilder('pf')
            ->join('pf.feature', 'f')
            ->where('pf.plan = :plan')
            ->andWhere('pf.enabled = :enabled')
            ->setParameter('plan', $plan)
            ->setParameter('enabled', true)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a specific feature value for a plan
     */
    public function findFeatureValue(SubscriptionPlan $plan, string $featureSlug): ?string
    {
        $result = $this->createQueryBuilder('pf')
            ->join('pf.feature', 'f')
            ->where('pf.plan = :plan')
            ->andWhere('f.slug = :slug')
            ->andWhere('pf.enabled = :enabled')
            ->setParameter('plan', $plan)
            ->setParameter('slug', $featureSlug)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getOneOrNullResult();

        return $result?->getValue();
    }

    /**
     * Check if a plan has a specific feature enabled
     */
    public function hasFeature(SubscriptionPlan $plan, string $featureSlug): bool
    {
        $count = $this->createQueryBuilder('pf')
            ->select('COUNT(pf.id)')
            ->join('pf.feature', 'f')
            ->where('pf.plan = :plan')
            ->andWhere('f.slug = :slug')
            ->andWhere('pf.enabled = :enabled')
            ->setParameter('plan', $plan)
            ->setParameter('slug', $featureSlug)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
