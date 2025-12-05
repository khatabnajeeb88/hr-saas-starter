<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
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
                $this->addFlash('error', 'Email is required.');
            } else {
                $user->setName($name);
                $user->setEmail($email);

                $entityManager->flush();

                $this->addFlash('success', 'Profile updated successfully.');
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
                $this->addFlash('error', 'Invalid current password.');
            } elseif ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New passwords do not match.');
            } elseif (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters.');
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                
                $entityManager->flush();

                $this->addFlash('success', 'Password changed successfully.');
                return $this->redirectToRoute('app_profile_edit');
            }
        }

        return $this->render('profile/change_password.html.twig');
    }
}
