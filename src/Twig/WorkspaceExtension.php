<?php

namespace App\Twig;

use App\Repository\TeamRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class WorkspaceExtension extends AbstractExtension implements GlobalsInterface
{
    private $requestStack;
    private $teamRepository;
    private $security;

    public function __construct(
        RequestStack $requestStack,
        TeamRepository $teamRepository,
        Security $security
    ) {
        $this->requestStack = $requestStack;
        $this->teamRepository = $teamRepository;
        $this->security = $security;
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [];
        }

        $currentTeamId = $request->getSession()->get('current_team_id');
        $currentTeam = null;

        if ($currentTeamId) {
            $currentTeam = $this->teamRepository->find($currentTeamId);
        }

        // Fallback or validation if needed (though subscriber handles most)
        
        return [
            'current_team' => $currentTeam,
        ];
    }
}
