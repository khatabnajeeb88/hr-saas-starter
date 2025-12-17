<?php

namespace App\Repository;

use App\Entity\EmployeeTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmployeeTag>
 *
 * @method EmployeeTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmployeeTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmployeeTag[]    findAll()
 * @method EmployeeTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeTag::class);
    }
}
