<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/onboarding')]
class OnboardingController extends AbstractController
{
    #[Route('/', name: 'app_onboarding_welcome')]
    public function welcome(): Response
    {
        return $this->render('onboarding/welcome.html.twig');
    }

    #[Route('/create', name: 'app_onboarding_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $teamName = $request->request->get('team_name');

        if (!$teamName) {
            $this->addFlash('error', 'Team name is required');
            return $this->redirectToRoute('app_onboarding_welcome');
        }

        $team = new Team();
        $team->setName($teamName);
        $team->setOwner($user);
        
        // Generate slug
        $slug = $slugger->slug($teamName)->lower()->slice(0, 50);
        // Ensure unique slug logic could be here, but for MVP we assume uniqueness or handle DB error
        // A robust implementation would check repository->isSlugAvailable
        $team->setSlug($slug);

        // Add owner as member
        $member = new TeamMember();
        $member->setUser($user);
        $member->setTeam($team);
        $member->setRole(TeamMember::ROLE_OWNER);
        
        $team->addMember($member);

        $em->persist($team);
        $em->persist($member);
        $em->flush();

        // Set as current team
        $request->getSession()->set('current_team_id', $team->getId());

        $this->addFlash('success', 'Workspace created successfully!');
        return $this->redirectToRoute('app_dashboard');
    }
}
