<?php

namespace App\Repository;

use App\Entity\EmployeeDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmployeeDocument>
 *
 * @method EmployeeDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmployeeDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmployeeDocument[]    findAll()
 * @method EmployeeDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployeeDocument::class);
    }

    public function save(EmployeeDocument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmployeeDocument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
