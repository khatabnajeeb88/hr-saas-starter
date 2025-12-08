<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{


    #[Route('/internal/notifications', name: 'internal_notifications_list', methods: ['GET'])]
    public function getNotifications(NotificationRepository $notificationRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([]);
        }

        // Fetch unread notifications, ordered by creation date descending
        $notifications = $notificationRepository->findBy(
            ['user' => $user, 'isRead' => false],
            ['createdAt' => 'DESC'],
            10 // Limit to 10 for now
        );

        $data = [];
        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'data' => $notification->getData(),
                'createdAt' => $notification->getCreatedAt()->format('c'),
                'isRead' => $notification->isRead(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/internal/notifications/{id}/read', name: 'internal_notification_mark_read', methods: ['POST'])]
    public function markAsRead(int $id, NotificationRepository $notificationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $notification = $notificationRepository->find($id);

        if (!$notification) {
            return $this->json(['error' => 'Notification not found'], 404);
        }

        if ($notification->getUser() !== $user) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $notification->setRead(true);
        $entityManager->flush();

        return $this->json(['message' => 'Marked as read']);
    }

    #[Route('/internal/notifications/read-all', name: 'internal_notification_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(NotificationRepository $notificationRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $notifications = $notificationRepository->findBy(['user' => $user, 'isRead' => false]);

        foreach ($notifications as $notification) {
            $notification->setRead(true);
        }

        $entityManager->flush();

        return $this->json(['message' => 'All marked as read']);
    }
}
