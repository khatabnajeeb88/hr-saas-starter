<?php

namespace App\Repository;

use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * Find all teams owned by a user
     *
     * @return Team[]
     */
    public function findByOwner(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all teams where a user is a member (including as owner)
     *
     * @return Team[]
     */
    public function findByMember(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.members', 'm')
            ->andWhere('m.user = :user OR t.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a team by its slug
     */
    public function findOneBySlug(string $slug): ?Team
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Check if a slug is available
     */
    public function isSlugAvailable(string $slug, ?int $excludeTeamId = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeTeamId !== null) {
            $qb->andWhere('t.id != :excludeId')
                ->setParameter('excludeId', $excludeTeamId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() === 0;
    }

    /**
     * Save a team entity
     */
    public function save(Team $team, bool $flush = false): void
    {
        $this->getEntityManager()->persist($team);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a team entity
     */
    public function remove(Team $team, bool $flush = false): void
    {
        $this->getEntityManager()->remove($team);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
