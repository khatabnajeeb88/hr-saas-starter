<?php

namespace App\EventSubscriber;

use App\Event\TeamMemberAddedEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private NotificationService $notificationService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TeamMemberAddedEvent::class => 'onTeamMemberAdded',
        ];
    }

    public function onTeamMemberAdded(TeamMemberAddedEvent $event): void
    {
        $teamMember = $event->getTeamMember();
        $user = $teamMember->getUser();
        $team = $teamMember->getTeam();

        // Notify the user that they have been added to a team
        $this->notificationService->notifyUser(
            $user,
            'team_invite',
            [
                'team_id' => $team->getId(),
                'team_name' => $team->getName(),
                'role' => $teamMember->getRole(),
                'message' => sprintf('You have been added to the team "%s" as %s.', $team->getName(), $teamMember->getRole()),
            ]
        );
    }
}
