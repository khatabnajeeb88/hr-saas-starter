<?php

namespace App\EventListener;

use App\Entity\Team;
use App\Entity\Traits\TenantAwareTrait;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TenantListener
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // For now, we'll just take the first team the user is a member of.
        // In a real app, you'd probably store the "current team" in the session or a header.
        $member = $user->getTeamMembers()->first();
        if (!$member) {
            return;
        }

        $team = $member->getTeam();
        if (!$team) {
            return;
        }

        $filter = $this->entityManager->getFilters()->getFilter('tenant_filter');
        $filter->setParameter('tenant_id', $team->getId());
    }

    #[AsEventListener(event: 'doctrine.prePersist')]
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // Check if entity uses TenantAwareTrait
        // We check for the method provided by the trait
        if (!method_exists($entity, 'getTenant')) {
            return;
        }

        if ($entity->getTenant()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Same logic: use first team
        $member = $user->getTeamMembers()->first();
        if ($member && $member->getTeam()) {
            $entity->setTenant($member->getTeam());
        }
    }
}
