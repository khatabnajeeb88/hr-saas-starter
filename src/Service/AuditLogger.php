<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RequestStack $requestStack
    ) {
    }

    public function log(string $action, ?string $entityType = null, ?string $entityId = null, ?array $changes = null, bool $flush = true): void
    {
        $log = new AuditLog();
        $log->setAction($action);
        $log->setEntityType($entityType);
        $log->setEntityId($entityId);
        $log->setChanges($changes);

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setUser($user);
            $log->setUserEmail($user->getEmail());
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $log->setIpAddress($request->getClientIp());
        }

        $this->entityManager->persist($log);
        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
