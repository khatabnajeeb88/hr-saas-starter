<?php

namespace App\Repository;

use App\Entity\EmploymentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmploymentType>
 *
 * @method EmploymentType|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmploymentType|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmploymentType[]    findAll()
 * @method EmploymentType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmploymentTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmploymentType::class);
    }
}
