<?php

namespace App\EventSubscriber;

use App\Service\AuditLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class AuditLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->auditLogger->log(
            action: 'LOGIN',
            entityType: 'User',
            entityId: (string) $event->getAuthenticationToken()->getUser()?->getId(),
            changes: ['event' => 'interactive_login']
        );
    }
}
