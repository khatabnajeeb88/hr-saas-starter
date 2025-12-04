<?php

namespace App\Repository;

use App\Entity\SubscriptionFeature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionFeature>
 */
class SubscriptionFeatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionFeature::class);
    }

    /**
     * Find all active features
     *
     * @return SubscriptionFeature[]
     */
    public function findActiveFeatures(): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a feature by slug
     */
    public function findBySlug(string $slug): ?SubscriptionFeature
    {
        return $this->createQueryBuilder('f')
            ->where('f.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find features by type
     *
     * @return SubscriptionFeature[]
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.featureType = :type')
            ->andWhere('f.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
