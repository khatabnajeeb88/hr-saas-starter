<?php

namespace App\MessageHandler;

use App\Entity\Notification;
use App\Message\NotificationMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NotificationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private HubInterface $hub,
    ) {
    }

    public function __invoke(NotificationMessage $message): void
    {
        $user = $this->userRepository->find($message->getUserId());

        if (!$user) {
            return;
        }

        // 1. Persist to Database
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($message->getType());
        $notification->setData($message->getData());
        
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        // 2. Push to Mercure
        // Topic: https://example.com/users/{id}/notifications
        // We'll use a placeholder domain or the app's configured domain.
        // For now, let's use a consistent pattern.
        $topic = sprintf('https://example.com/users/%d/notifications', $user->getId());
        
        $update = new Update(
            $topic,
            json_encode([
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'data' => $notification->getData(),
                'createdAt' => $notification->getCreatedAt()->format('c'),
            ])
        );

        $this->hub->publish($update);

        // 3. Send Email (Optional - can be added here or as a separate handler)
        // For now, we'll skip email to keep it simple, or add a TODO.
    }
}
