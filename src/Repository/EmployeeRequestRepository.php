<?php

namespace App\Repository;

use App\Entity\EmployeeRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmployeeRequest>
 *
 * @method EmployeeRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmployeeRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmployeeRequest[]    findAll()
 * @method EmployeeRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeRequest::class);
    }
}
