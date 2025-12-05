<?php

namespace App\Repository;

use App\Entity\TeamInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamInvitation>
 *
 * @method TeamInvitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeamInvitation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TeamInvitation[]    findAll()
 * @method TeamInvitation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamInvitation::class);
    }

    public function save(TeamInvitation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TeamInvitation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
