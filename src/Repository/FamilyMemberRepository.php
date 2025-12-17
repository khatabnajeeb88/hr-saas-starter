<?php

namespace App\Repository;

use App\Entity\FamilyMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FamilyMember>
 *
 * @method FamilyMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method FamilyMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method FamilyMember[]    findAll()
 * @method FamilyMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FamilyMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FamilyMember::class);
    }
}
