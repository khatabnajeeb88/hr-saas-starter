<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationTest extends WebTestCase
{
    public function testRegistrationPageLoads()
    {
        $client = static::createClient();
        $client->request('GET', '/en/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Get Started');
    }

    public function testSuccessfulRegistration()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/register');

        $email = 'test_register_'.uniqid().'@example.com';
        
        $form = $crawler->selectButton('Create Account')->form();
        $form['registration_form[email]'] = $email;
        $form['registration_form[plainPassword]'] = 'StrongP@ssw0rd!';
        $form['registration_form[agreeTerms]'] = 1;

        $client->submit($form);

        $this->assertResponseRedirects('/en/dashboard');
        
        // Verify user exists
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        
        $this->assertNotNull($user);
        $this->assertEquals($email, $user->getEmail());
    }
}
