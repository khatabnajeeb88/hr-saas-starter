<?php

namespace App\Repository;

use App\Entity\EmployeeContract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmployeeContract>
 *
 * @method EmployeeContract|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmployeeContract|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmployeeContract[]    findAll()
 * @method EmployeeContract[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeContract::class);
    }
}
