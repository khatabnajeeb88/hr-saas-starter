<?php

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: Events::onFlush)]
class AuditDoctrineSubscriber implements EventSubscriber
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->logChange($em, $entity, 'CREATE', $uow->getEntityChangeSet($entity));
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->logChange($em, $entity, 'UPDATE', $uow->getEntityChangeSet($entity));
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->logChange($em, $entity, 'DELETE', [], $uow->getOriginalEntityData($entity));
        }
    }

    private function logChange($em, object $entity, string $action, array $changeSet = [], array $originalData = []): void
    {
        if ($entity instanceof AuditLog) {
            return;
        }

        $entityClass = get_class($entity);
        if (str_contains($entityClass, '__CG__')) {
             $entityClass = get_parent_class($entity);
        }

        $entityId = method_exists($entity, 'getId') ? (string) $entity->getId() : null;

        $cleanChanges = [];
        if ($action === 'DELETE') {
             // For delete, we might want to store the original data as "changes" or a separate field
             // Storing as changes with 'from' values
             foreach ($originalData as $field => $value) {
                 $cleanChanges[$field] = [
                     'from' => $this->normalizeValue($value),
                     'to' => null,
                 ];
             }
        } else {
            foreach ($changeSet as $field => $values) {
                // $values[0] is old, $values[1] is new
                $cleanChanges[$field] = [
                    'from' => $this->normalizeValue($values[0]),
                    'to' => $this->normalizeValue($values[1]),
                ];
            }
        }
        
        if ($action === 'UPDATE' && empty($cleanChanges)) {
            return;
        }

        $log = new AuditLog();
        $log->setAction($action);
        $log->setEntityType($entityClass);
        $log->setEntityId($entityId);
        $log->setChanges($cleanChanges);

        // Populate User and IP
        try {
            $user = $this->security->getUser();
            if ($user instanceof User) {
                $log->setUser($user);
                $log->setUserEmail($user->getEmail());
            }
        } catch (\Exception $e) {
            // Context might not be available
        }

        try {
            $request = $this->requestStack->getCurrentRequest();
            if ($request) {
                $log->setIpAddress($request->getClientIp());
            }
        } catch (\Exception $e) {
            // Request might not be available
        }
        
        $em->persist($log);
        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata(AuditLog::class), $log);
    }
    
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }
        if (is_object($value) && method_exists($value, 'getId')) {
            return get_class($value) . '#' . $value->getId();
        }
        if (is_object($value)) {
            // Attempt to stringify if possible, or just class name
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            return get_class($value);
        }
        return $value;
    }
}
