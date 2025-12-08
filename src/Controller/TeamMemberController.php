<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamInvitation;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Repository\TeamInvitationRepository;
use App\Service\TeamManager;
use App\Security\Voter\TeamVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/teams/{id}/members')]
#[IsGranted('ROLE_USER')]
class TeamMemberController extends AbstractController
{
    #[Route('/', name: 'app_team_members', methods: ['GET'])]
    public function index(Team $team): Response
    {
        $this->denyAccessUnlessGranted(TeamVoter::VIEW, $team);

        return $this->render('team/members.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/invite', name: 'app_team_invite', methods: ['POST'])]
    public function invite(
        Team $team,
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $this->denyAccessUnlessGranted(TeamVoter::MANAGE_MEMBERS, $team);

        $email = $request->request->get('email');
        $role = $request->request->get('role', TeamMember::ROLE_MEMBER);

        if (empty($email)) {
            $this->addFlash('error', 'Email is required.');
            return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
        }

        // Check if already a member
        foreach ($team->getMembers() as $member) {
            if ($member->getUser()->getEmail() === $email) {
                $this->addFlash('error', 'User is already a member of this team.');
                return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
            }
        }

        // Create invitation
        $invitation = new TeamInvitation();
        $invitation->setEmail($email);
        $invitation->setTeam($team);
        $invitation->setRole($role);
        $invitation->setToken(Uuid::v4()->toBase58());

        $entityManager->persist($invitation);
        $entityManager->flush();

        // Send email
        $emailMessage = (new TemplatedEmail())
            ->from(new Address('support@saas-starter.com', 'SaaS Starter Support'))
            ->to($email)
            ->subject('You have been invited to join ' . $team->getName())
            ->htmlTemplate('email/team_invitation.html.twig')
            ->context([
                'invitation' => $invitation,
                'team' => $team,
            ]);

        $mailer->send($emailMessage);

        $this->addFlash('success', 'Invitation sent successfully.');

        return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
    }

    #[Route('/remove/{memberId}', name: 'app_team_remove_member', methods: ['POST'])]
    public function remove(
        Team $team,
        int $memberId,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted(TeamVoter::MANAGE_MEMBERS, $team);

        $member = $entityManager->getRepository(TeamMember::class)->find($memberId);

        if (!$member || $member->getTeam() !== $team) {
            throw $this->createNotFoundException('Member not found.');
        }

        if ($member->getRole() === TeamMember::ROLE_OWNER) {
            $this->addFlash('error', 'Cannot remove the team owner.');
            return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
        }

        $entityManager->remove($member);
        $entityManager->flush();

        $this->addFlash('success', 'Member removed successfully.');

        return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
    }

    #[Route('/role/{memberId}', name: 'app_team_change_role', methods: ['POST'])]
    public function changeRole(
        Team $team,
        int $memberId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted(TeamVoter::MANAGE_MEMBERS, $team);

        $member = $entityManager->getRepository(TeamMember::class)->find($memberId);
        $newRole = $request->request->get('role');

        if (!$member || $member->getTeam() !== $team) {
            throw $this->createNotFoundException('Member not found.');
        }

        if (!in_array($newRole, TeamMember::ROLES)) {
            $this->addFlash('error', 'Invalid role.');
            return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
        }

        if ($member->getRole() === TeamMember::ROLE_OWNER) {
            $this->addFlash('error', 'Cannot change role of the team owner.');
            return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
        }

        $member->setRole($newRole);
        $entityManager->flush();

        $this->addFlash('success', 'Role updated successfully.');

        return $this->redirectToRoute('app_team_members', ['id' => $team->getId()]);
    }
}
