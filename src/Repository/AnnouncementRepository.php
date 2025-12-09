<?php

namespace App\Repository;

use App\Entity\Announcement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Announcement>
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    /**
     * Find active announcements not read by the user
     * @return Announcement[]
     */
    public function findUnreadActiveForUser(User $user): array
    {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('a');
        
        return $qb->where('a.isActive = :active')
            ->andWhere('a.startAt <= :now')
            ->andWhere('a.endAt IS NULL OR a.endAt >= :now')
            ->andWhere(':user NOT MEMBER OF a.readByUsers')
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('a.startAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
