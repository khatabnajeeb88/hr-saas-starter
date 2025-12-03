<?php

namespace App\Service;

use App\Entity\User;
use App\Message\NotificationMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class NotificationService
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    public function notifyUser(User $user, string $type, array $data = []): void
    {
        $this->bus->dispatch(new NotificationMessage($user->getId(), $type, $data));
    }
}
