<?php

namespace App\Tests\Functional;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileApiTokenTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Create a user for testing
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->user = $userRepository->findOneBy(['email' => 'profile_token_test@example.com']);

        if (!$this->user) {
            $this->user = new User();
            $this->user->setEmail('profile_token_test@example.com');
            $this->user->setPassword('password');
            $this->user->setIsVerified(true);
            $this->entityManager->persist($this->user);
            $this->entityManager->flush();

            // Create a team for the user to bypass onboarding
            $team = new \App\Entity\Team();
            $team->setName('Test Team');
            $team->setOwner($this->user);
            $team->setSlug('test-team-' . uniqid());
            $this->entityManager->persist($team);

            $member = new \App\Entity\TeamMember();
            $member->setUser($this->user);
            $member->setTeam($team);
            $member->setRole('owner'); // distinct from string constant if needed, but safe here
            $this->entityManager->persist($member);
            
            $this->entityManager->persist($member);
            
            $this->entityManager->flush();
        }

        // Ensure user has at least one team (for retry robustness)
        if ($this->user->getTeamMembers()->isEmpty() && $this->entityManager->getRepository(\App\Entity\Team::class)->findByOwner($this->user) === []) {
             $team = new \App\Entity\Team();
            $team->setName('Test Team Retry');
            $team->setOwner($this->user);
            $team->setSlug('test-team-' . uniqid());
            $this->entityManager->persist($team);

            $member = new \App\Entity\TeamMember();
            $member->setUser($this->user);
            $member->setTeam($team);
            $member->setRole('owner');
            $this->entityManager->persist($member);
            
            $this->entityManager->flush();
        }
    }

    public function testGetApiTokens()
    {
        $this->client->loginUser($this->user);

        // Create a token manually
        $token = new ApiToken();
        $token->setDescription('Test Token');
        $this->user->addApiToken($token);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->client->request('GET', '/en/profile/api-tokens');

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($content);
        $this->assertGreaterThanOrEqual(1, count($content));
        $this->assertEquals('Test Token', $content[0]['description']);
        $this->assertArrayHasKey('maskedToken', $content[0]);
    }

    public function testCreateApiToken()
    {
        $this->client->loginUser($this->user);

        $this->client->request('POST', '/en/profile/api-tokens', [], [], [], json_encode(['description' => 'New UI Token']));

        $this->assertResponseIsSuccessful();
        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('New UI Token', $content['description']);
        $this->assertArrayHasKey('token', $content); // Should return full token on create
    }

    public function testRevokeApiToken()
    {
        $this->client->loginUser($this->user);

        // Create a token to revoke
        $token = new ApiToken();
        $token->setUser($this->user);
        $token->setDescription('To Revoke');
        $this->entityManager->persist($token);
        $this->entityManager->flush();
        $tokenId = $token->getId();

        $this->client->request('DELETE', '/en/profile/api-tokens/' . $tokenId);

        $this->assertResponseIsSuccessful();

        // Verify it's gone
        $this->entityManager->clear(); // Clear cache
        $deletedToken = $this->entityManager->getRepository(ApiToken::class)->find($tokenId);
        $this->assertNull($deletedToken);
    }
}
