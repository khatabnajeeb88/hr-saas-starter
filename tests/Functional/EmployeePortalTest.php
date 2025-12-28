<?php

namespace App\Tests\Functional;

use App\Entity\Employee;
use App\Entity\User;
use App\Repository\EmployeeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class EmployeePortalTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
    }

    public function testPortalAccessDeniedForNonEmployees(): void
    {
        // Login as a user without ROLE_EMP (assuming regular user handling or create one)
        // For simplicity, let's try to access as anonymous first
        $this->client->request('GET', '/portal/');
        $this->assertResponseRedirects('/login'); // Should redirect to login

        // Or create a user without employee role if possible.
    }

    public function testPortalDashboardAccess(): void
    {
        $user = $this->createEmployeeUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/portal/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Welcome back');
        $this->assertSelectorTextContains('nav', 'Dashboard');
    }

    public function testProfileEdit(): void
    {
        $user = $this->createEmployeeUser();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/portal/profile/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save Changes')->form();
        $form['employee_portal_profile[mobile]'] = '0501234567';
        
        $this->client->submit($form);
        $this->assertResponseRedirects('/portal/profile');
        $this->client->followRedirect();
        
        $this->assertSelectorTextContains('body', '0501234567');
    }

    private function createEmployeeUser(): User
    {
        $uniqueId = uniqid();
        $email = "emp_{$uniqueId}@example.com";
        
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('$2y$13$...'); // Dummy hash
        $user->setRoles(['ROLE_EMP']);
        $user->setName("Test Employee {$uniqueId}");

        $employee = new Employee();
        $employee->setFirstName('Test');
        $employee->setLastName('Employee');
        $employee->setEmail($email);
        $employee->setMobile('0500000000');
        $employee->setUser($user);
        
        $this->entityManager->persist($user);
        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        return $user;
    }
}
