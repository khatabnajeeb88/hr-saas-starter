<?php

namespace App\Tests\Functional;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuditLogTest extends WebTestCase
{
    public function testLoginIsLogged(): void
    {
        $client = static::createClient();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        
        $user = new User();
        $user->setEmail('login_test_' . uniqid() . '@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setName('Admin');
        
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        // $client->loginUser($user); // Does not trigger INTERACTIVE_LOGIN
        
        // We need to perform a real login to trigger the event
        $crawler = $client->request('GET', '/en/login');
        
        // Try to select by button text matching template default or filter
        $button = $crawler->selectButton('Sign In');
        if ($button->count() === 0) {
             // Fallback to "Sign in" or generic submit
             $button = $crawler->filter('button[type="submit"]');
        }
        
        if ($button->count() === 0) {
            $content = $client->getResponse()->getContent();
            $status = $client->getResponse()->getStatusCode();
            $url = $client->getRequest()->getUri();
            throw new \Exception("Login button not found on $url (Status: $status). Content excerpt: " . substr(strip_tags($content), 0, 500));
        }
        
        $form = $button->form();
        $form['email'] = $user->getEmail();
        $form['password'] = 'password';
        $client->submit($form);
        
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();
        
        // Check if we are really logged in
        // Search for logout link or specific dashboard element, or check URL not being /login
        // Assuming success redirects to / or /dashboard
        // If failed, we are likely back at /login
        if ($client->getRequest()->getPathInfo() === '/en/login') {
             throw new \Exception("Login failed. Redirected back to login page. Response: " . substr(strip_tags($client->getResponse()->getContent()), 0, 500));
        }

        /** @var AuditLogRepository $auditRepo */
        $auditRepo = static::getContainer()->get(AuditLogRepository::class);
        $log = $auditRepo->findOneBy(['action' => 'LOGIN', 'user' => $user], ['id' => 'DESC']);

        $this->assertNotNull($log, 'Login audit log not found');
        $this->assertEquals('User', $log->getEntityType());
        $this->assertEquals($user->getId(), $log->getEntityId());
    }

    public function testEntityUpdateIsLogged(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user = new User();
        $user->setEmail('update_test_' . uniqid() . '@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $entityManager->persist($user);
        $entityManager->flush();
        
        $client->loginUser($user);

        // Refresh user to ensure we are working with the managed entity in this scope
        // or just update it via the repository which uses the manager
        $user = $entityManager->getRepository(User::class)->find($user->getId());
        
        // Update user name via some mechanism or directly in code for this test context?
        // Since this is a functional test, we might want to trigger an update.
        // Let's rely on the EntityManager directly to trigger the Doctrine event, 
        // effectively testing the Subscriber logic integration with Doctrine.
        
        $user->setName('Updated Name ' . uniqid());
        $entityManager->flush();

        /** @var AuditLogRepository $auditRepo */
        $auditRepo = static::getContainer()->get(AuditLogRepository::class);
        $log = $auditRepo->findOneBy(['action' => 'UPDATE', 'entityType' => User::class, 'entityId' => (string)$user->getId()], ['id' => 'DESC']);

        $this->assertNotNull($log, 'Update audit log not found');
        $this->assertArrayHasKey('name', $log->getChanges());
    }
}
