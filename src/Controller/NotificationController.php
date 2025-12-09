<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\AnnouncementRepository;


#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    #[Route('/internal/notifications', name: 'internal_notifications_list', methods: ['GET'])]
    public function getNotifications(
        NotificationRepository $notificationRepository,
        AnnouncementRepository $announcementRepository
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json([]);
        }

        // 1. Fetch unread notifications
        $notifications = $notificationRepository->findBy(
            ['user' => $user, 'isRead' => false],
            ['createdAt' => 'DESC'],
            10
        );

        $data = [];

        // 2. Fetch unread announcements
        $announcements = $announcementRepository->findUnreadActiveForUser($user);

        foreach ($announcements as $announcement) {
            $data[] = [
                'id' => 'announcement_' . $announcement->getId(),
                'type' => $announcement->getType(), // info, warning, danger
                'data' => [
                    'message' => $announcement->getTitle(), // Use title as main message
                    'description' => $announcement->getMessage(),
                    'is_announcement' => true,
                ],
                'createdAt' => $announcement->getStartAt()->format('c'),
                'isRead' => false,
            ];
        }

        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'data' => $notification->getData(),
                'createdAt' => $notification->getCreatedAt()->format('c'),
                'isRead' => $notification->isRead(),
            ];
        }
        
        // Sort combined list by date desc
        usort($data, fn($a, $b) => $b['createdAt'] <=> $a['createdAt']);

        return $this->json($data);
    }

    #[Route('/internal/notifications/{id}/read', name: 'internal_notification_mark_read', methods: ['POST'])]
    public function markAsRead(
        string $id, 
        NotificationRepository $notificationRepository, 
        AnnouncementRepository $announcementRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Check if it is an announcement
        if (str_starts_with($id, 'announcement_')) {
            $announcementId = (int) substr($id, strlen('announcement_'));
            $announcement = $announcementRepository->find($announcementId);

            if ($announcement) {
                $announcement->addReadByUser($user);
                $entityManager->flush();
                return $this->json(['message' => 'Announcement marked as read']);
            }
            return $this->json(['error' => 'Announcement not found'], 404);
        }

        // It is a notification
        $notification = $notificationRepository->find((int) $id);

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
    public function markAllAsRead(
        NotificationRepository $notificationRepository, 
        AnnouncementRepository $announcementRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // 1. Mark notifications
        $notifications = $notificationRepository->findBy(['user' => $user, 'isRead' => false]);
        foreach ($notifications as $notification) {
            $notification->setRead(true);
        }

        // 2. Mark announcements
        $announcements = $announcementRepository->findUnreadActiveForUser($user);
        foreach ($announcements as $announcement) {
            $announcement->addReadByUser($user);
        }

        $entityManager->flush();

        return $this->json(['message' => 'All marked as read']);
    }
}
