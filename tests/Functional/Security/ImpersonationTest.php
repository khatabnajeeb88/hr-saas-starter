<?php

namespace App\Tests\Functional\Security;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImpersonationTest extends WebTestCase
{
    public function testAdminCanImpersonateUser(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $hasher = $container->get('security.user_password_hasher');

        // Create Admin
        $admin = new User();
        $admin->setEmail('admin_imp_' . uniqid() . '@example.com');
        $admin->setPassword($hasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $em->persist($admin);

        $adminTeam = new \App\Entity\Team();
        $adminTeam->setName('Admin Team');
        $adminTeam->setOwner($admin);
        $adminTeam->setSlug('admin-team-' . uniqid());
        $em->persist($adminTeam);

        $adminMember = new \App\Entity\TeamMember();
        $adminMember->setUser($admin);
        $adminMember->setTeam($adminTeam);
        $adminMember->setRole('owner');
        $em->persist($adminMember);

        // Create User
        $user = new User();
        $user->setEmail('user_imp_' . uniqid() . '@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $em->persist($user);

        $userTeam = new \App\Entity\Team();
        $userTeam->setName('User Team');
        $userTeam->setOwner($user);
        $userTeam->setSlug('user-team-' . uniqid());
        $em->persist($userTeam);

        $userMember = new \App\Entity\TeamMember();
        $userMember->setUser($user);
        $userMember->setTeam($userTeam);
        $userMember->setRole('owner');
        $em->persist($userMember);
        
        $em->flush();

        // 2. Login as Admin
        $client->loginUser($admin);

        // 3. Request homepage with switch_user
        $client->request('GET', '/en/dashboard', ['_switch_user' => $user->getEmail()]);
        
        // Follow redirect if it happens (it usually does for switch_user to clear the param)
        if ($client->getResponse()->isRedirect()) {
            $client->followRedirect();
        }
        
        // 4. Verify we are content shows impersonation
        $this->assertResponseIsSuccessful();
        
        // Check if we are seeing the page as the user.
        // The simple way is to check for the impersonation banner we added.
        $this->assertSelectorTextContains('body', 'Impersonation Mode');
        $this->assertSelectorTextContains('body', 'You are currently acting as ' . $user->getEmail());

        // 5. Exit impersonation
        $client->request('GET', '/en/dashboard', ['_switch_user' => '_exit']);
        
        // Follow redirect after exit
        if ($client->getResponse()->isRedirect()) {
            $client->followRedirect();
        }

        $this->assertResponseIsSuccessful();
        
        // Banner should be gone
        $this->assertSelectorNotExists('.bg-yellow-100'); // Class of the banner
    }

    public function testUserCannotImpersonate(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $hasher = $container->get('security.user_password_hasher');
        
        $user = new User();
        $user->setEmail('user_fail_imp_' . uniqid() . '@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $em->persist($user);

        $target = new User();
        $target->setEmail('target_imp_' . uniqid() . '@example.com');
        $target->setPassword($hasher->hashPassword($target, 'password'));
        $target->setRoles(['ROLE_USER']);
        $em->persist($target);

        $em->flush();

        $client->loginUser($user);
        
        $client->request('GET', '/en/dashboard', ['_switch_user' => $target->getEmail()]);
        
        // Should be forbidden
        $this->assertResponseStatusCodeSame(403);
    }
}
