<?php

namespace App\Tests\Functional\Admin;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminDashboardTest extends WebTestCase
{
    public function testAdminDashboardRedirectsAnonymous()
    {
        $client = static::createClient();
        $client->request('GET', '/en/admin');

        $this->assertResponseRedirects('/en/login');
    }

    public function testAdminDashboardDeniesRegularUser()
    {
        $client = static::createClient();
        
        // Create a regular user
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('user_' . uniqid() . '@example.com');
        $user->setName('Regular User');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        
        $entityManager->persist($user);
        $entityManager->persist($user);

        // Create a team for the user to bypass onboarding
        $team = new \App\Entity\Team();
        $team->setName('User Team');
        $team->setSlug('user-team-' . uniqid());
        $team->setOwner($user);
        $entityManager->persist($team);

        $teamMember = new \App\Entity\TeamMember();
        $teamMember->setUser($user);
        $teamMember->setTeam($team);
        $teamMember->setRole(\App\Entity\TeamMember::ROLE_OWNER);
        $entityManager->persist($teamMember);

        $entityManager->flush();

        // Login as regular user
        $client->loginUser($user);
        $client->request('GET', '/en/admin');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminDashboardAllowsAdminUser()
    {
        $client = static::createClient();
        
        // Create an admin user
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $admin = new User();
        $admin->setEmail('admin_' . uniqid() . '@example.com');
        $admin->setName('Admin User');
        $admin->setPassword($hasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        
        $entityManager->persist($admin);
        $entityManager->persist($admin);

        // Create a team for the admin to bypass onboarding
        $team = new \App\Entity\Team();
        $team->setName('Admin Team');
        $team->setSlug('admin-team-' . uniqid());
        $team->setOwner($admin);
        $entityManager->persist($team);

        $teamMember = new \App\Entity\TeamMember();
        $teamMember->setUser($admin);
        $teamMember->setTeam($team);
        $teamMember->setRole(\App\Entity\TeamMember::ROLE_OWNER);
        $entityManager->persist($teamMember);

        $entityManager->flush();

        // Login as admin
        $client->loginUser($admin);
        $client->request('GET', '/en/admin');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'SaaS Admin');
    }
}
