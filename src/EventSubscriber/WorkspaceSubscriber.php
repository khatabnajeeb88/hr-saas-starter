<?php

namespace App\EventSubscriber;

use App\Controller\OnboardingController;
use App\Entity\User;
use App\Entity\Team;
use App\Repository\TeamRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WorkspaceSubscriber implements EventSubscriberInterface
{
    private $security;
    private $urlGenerator;
    private $teamRepository;

    public function __construct(
        Security $security,
        UrlGeneratorInterface $urlGenerator,
        TeamRepository $teamRepository
    ) {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
        $this->teamRepository = $teamRepository;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Avoid infinite loops for onboarding routes, asset files, or profiler
        if (str_starts_with($route, 'app_onboarding_') || 
            str_starts_with($route, '_wdt') || 
            str_starts_with($route, '_profiler') ||
            str_starts_with($route, 'app_logout') ||
            str_starts_with($request->getPathInfo(), '/api/')
           ) {
            return;
        }

        // Check if user has any teams
        $teams = $this->teamRepository->findTeamsByUser($user);

        if (empty($teams)) {
            // No teams -> redirect to onboarding
            $url = $this->urlGenerator->generate('app_onboarding_welcome');
            $event->setResponse(new RedirectResponse($url));
            return;
        }

        // User has teams, check session for current_team_id
        $session = $request->getSession();
        $currentTeamId = $session->get('current_team_id');

        if (!$currentTeamId) {
            // No current team set, set the first one
            $firstTeam = $teams[0];
            $session->set('current_team_id', $firstTeam->getId());
        } else {
            // Verify the current team ID actually belongs to the user
            $isValid = false;
            foreach ($teams as $team) {
                if ($team->getId() === $currentTeamId) {
                    $isValid = true;
                    break;
                }
            }

            if (!$isValid) {
                // Invalid team ID (maybe removed or no longer member), reset to first
                $firstTeam = $teams[0];
                $session->set('current_team_id', $firstTeam->getId());
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
