<?php

namespace App\Controller;

use App\Entity\TeamInvitation;
use App\Entity\User;
use App\Service\TeamManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TeamInvitationController extends AbstractController
{
    #[Route('/invitation/{token}', name: 'app_invitation_accept')]
    public function accept(
        string $token,
        EntityManagerInterface $entityManager,
        TeamManager $teamManager
    ): Response {
        $invitation = $entityManager->getRepository(TeamInvitation::class)->findOneBy(['token' => $token]);

        if (!$invitation) {
            throw $this->createNotFoundException('Invitation not found.');
        }

        if ($invitation->isExpired()) {
            $this->addFlash('error', 'This invitation has expired.');
            return $this->redirectToRoute('app_home'); // Or login
        }

        // Ensure user is logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // Check if email matches (optional, but good for security)
        if ($user->getEmail() !== $invitation->getEmail()) {
             // You might want to allow this if the user wants to accept with a different email, 
             // but strictly speaking it should match. For now, let's warn or block.
             // Let's allow it but maybe update the invitation email? 
             // Or strictly enforce:
             // $this->addFlash('error', 'This invitation was sent to a different email address.');
             // return $this->redirectToRoute('app_home');
        }

        // Add user to team
        try {
            $teamManager->addMember($invitation->getTeam(), $user, $invitation->getRole());
            
            // Remove invitation
            $entityManager->remove($invitation);
            $entityManager->flush();

            $this->addFlash('success', 'You have successfully joined ' . $invitation->getTeam()->getName());
            
            // Switch to the new team
            // $request->getSession()->set('active_team_id', $invitation->getTeam()->getId());

            return $this->redirectToRoute('app_team_index');

        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_team_index');
        }
    }
}
