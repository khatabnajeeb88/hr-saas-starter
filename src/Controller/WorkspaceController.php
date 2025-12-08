<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workspace')]
class WorkspaceController extends AbstractController
{
    #[Route('/switch/{id}', name: 'app_workspace_switch')]
    public function switch(int $id, Request $request, TeamRepository $teamRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verify user is member of team
        $team = $teamRepository->find($id);
        if (!$team || !$team->hasMember($user)) {
            $this->addFlash('error', 'You are not a member of this workspace.');
            return $this->redirectToRoute('app_dashboard');
        }

        $request->getSession()->set('current_team_id', $team->getId());
        
        // return $this->redirectToRoute('app_dashboard');
        return $this->redirect($request->headers->get('referer', $this->generateUrl('app_dashboard')));
    }
}
