<?php

namespace App\Repository;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamMember>
 */
class TeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamMember::class);
    }

    /**
     * Find a team member by team and user
     */
    public function findOneByTeamAndUser(Team $team, User $user): ?TeamMember
    {
        return $this->createQueryBuilder('tm')
            ->andWhere('tm.team = :team')
            ->andWhere('tm.user = :user')
            ->setParameter('team', $team)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all members of a team
     *
     * @return TeamMember[]
     */
    public function findByTeam(Team $team): array
    {
        return $this->createQueryBuilder('tm')
            ->andWhere('tm.team = :team')
            ->setParameter('team', $team)
            ->orderBy('tm.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all team memberships for a user
     *
     * @return TeamMember[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('tm')
            ->andWhere('tm.user = :user')
            ->setParameter('user', $user)
            ->orderBy('tm.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count members in a team
     */
    public function countByTeam(Team $team): int
    {
        return (int) $this->createQueryBuilder('tm')
            ->select('COUNT(tm.id)')
            ->andWhere('tm.team = :team')
            ->setParameter('team', $team)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find members by role in a team
     *
     * @return TeamMember[]
     */
    public function findByTeamAndRole(Team $team, string $role): array
    {
        return $this->createQueryBuilder('tm')
            ->andWhere('tm.team = :team')
            ->andWhere('tm.role = :role')
            ->setParameter('team', $team)
            ->setParameter('role', $role)
            ->orderBy('tm.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save a team member entity
     */
    public function save(TeamMember $teamMember, bool $flush = false): void
    {
        $this->getEntityManager()->persist($teamMember);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a team member entity
     */
    public function remove(TeamMember $teamMember, bool $flush = false): void
    {
        $this->getEntityManager()->remove($teamMember);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
