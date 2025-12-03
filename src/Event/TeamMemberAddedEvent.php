<?php

namespace App\Event;

use App\Entity\TeamMember;
use Symfony\Contracts\EventDispatcher\Event;

class TeamMemberAddedEvent extends Event
{
    public function __construct(
        private TeamMember $teamMember
    ) {
    }

    public function getTeamMember(): TeamMember
    {
        return $this->teamMember;
    }
}
