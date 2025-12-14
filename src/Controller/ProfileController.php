<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');

            // Basic validation
            if (empty($email)) {
                $this->addFlash('error', 'flash.error.email_required');
            } else {
                $user->setName($name);
                $user->setEmail($email);

                $entityManager->flush();

                $this->addFlash('success', 'flash.success.profile_updated');
                return $this->redirectToRoute('app_profile_edit');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'flash.error.invalid_current_password');
            } elseif ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'flash.error.password_mismatch');
            } elseif (strlen($newPassword) < 6) {
                $this->addFlash('error', 'flash.error.password_short');
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                
                $entityManager->flush();

                $this->addFlash('success', 'flash.success.password_changed');
                return $this->redirectToRoute('app_profile_edit');
            }
        }

        return $this->render('profile/change_password.html.twig');
    }
    #[Route('/api-tokens', name: 'app_profile_api_tokens_list', methods: ['GET'])]
    public function getApiTokens(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $tokens = [];

        foreach ($user->getApiTokens() as $token) {
            $tokens[] = [
                'id' => $token->getId(),
                'description' => $token->getDescription(),
                'maskedToken' => $token->getMaskedToken(),
                'lastUsedAt' => $token->getLastUsedAt()?->format('c'),
                'createdAt' => $token->getCreatedAt()->format('c'),
            ];
        }

        return $this->json($tokens);
    }

    #[Route('/api-tokens', name: 'app_profile_api_tokens_create', methods: ['POST'])]
    public function createApiToken(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $description = $data['description'] ?? 'Default Token';

        $token = new \App\Entity\ApiToken();
        $token->setUser($user);
        $token->setDescription($description);
        
        $entityManager->persist($token);
        $entityManager->flush();

        return $this->json([
            'id' => $token->getId(),
            'description' => $token->getDescription(),
            'token' => $token->getToken(), // Return full token ONLY here
            'maskedToken' => $token->getMaskedToken(),
            'lastUsedAt' => null,
            'createdAt' => $token->getCreatedAt()->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api-tokens/{id}', name: 'app_profile_api_tokens_revoke', methods: ['DELETE'])]
    public function revokeApiToken(\App\Entity\ApiToken $token, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($token->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($token);
        $entityManager->flush();

        return $this->json([], Response::HTTP_NO_CONTENT);
    }
}
