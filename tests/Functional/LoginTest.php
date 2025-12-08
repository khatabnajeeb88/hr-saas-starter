<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginTest extends WebTestCase
{
    public function testLoginPageLoads()
    {
        $client = static::createClient();
        $client->request('GET', '/en/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome Back');
    }

    public function testSuccessfulLogin()
    {
        $client = static::createClient();
        
        // Create user
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test_login_'.uniqid().'@example.com';
        $user = new User();
        $user->setEmail($email);
        $user->setName('Test User');
        $user->setPassword($hasher->hashPassword($user, 'password123'));
        
        $em->persist($user);
        $em->flush();

        $crawler = $client->request('GET', '/en/login');
        
        $form = $crawler->selectButton('Sign In')->form();
        $form['email'] = $email;
        $form['password'] = 'password123';
        
        $client->submit($form);

        $this->assertResponseRedirects('/en/dashboard');
    }

    public function testInvalidCredentials()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/login');
        
        $form = $crawler->selectButton('Sign In')->form();
        $form['email'] = 'invalid@example.com';
        $form['password'] = 'wrongpassword';
        
        $client->submit($form);

        $this->assertResponseRedirects('/en/login');
        $client->followRedirect();
        
        $this->assertSelectorExists('.bg-red-50'); // Error alert check
    }
}
