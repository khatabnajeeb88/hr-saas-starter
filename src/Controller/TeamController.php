<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Service\TeamManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/teams')]
#[IsGranted('ROLE_USER')]
class TeamController extends AbstractController
{
    #[Route('/', name: 'app_team_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->render('team/index.html.twig', [
            'teams' => $user->getTeamMembers(),
        ]);
    }

    #[Route('/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TeamManager $teamManager): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            
            if (empty($name)) {
                $this->addFlash('error', 'Team name is required.');
            } else {
                /** @var User $user */
                $user = $this->getUser();
                $team = $teamManager->createTeam($user, $name);
                
                $this->addFlash('success', 'Team created successfully.');
                return $this->redirectToRoute('app_team_index');
            }
        }

        return $this->render('team/new.html.twig');
    }

    #[Route('/{id}/switch', name: 'app_team_switch', methods: ['POST'])]
    public function switch(Team $team, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Check if user is member of this team
        $isMember = false;
        foreach ($user->getTeamMembers() as $member) {
            if ($member->getTeam() === $team) {
                $isMember = true;
                break;
            }
        }

        if (!$isMember) {
            throw $this->createAccessDeniedException('You are not a member of this team.');
        }

        // Store active team ID in session
        $request->getSession()->set('active_team_id', $team->getId());
        
        $this->addFlash('success', 'Switched to team ' . $team->getName());
        
        // Redirect to dashboard or previous page
        return $this->redirectToRoute('app_team_index');
    }
}
