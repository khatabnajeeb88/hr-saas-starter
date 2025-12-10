<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiTokenTest extends WebTestCase
{
    public function testApiTokenFlow(): void
    {
        $client = static::createClient();
        
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $hasher = $container->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail('apitoken_test_' . uniqid() . '@example.com');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setName('Api Token User');
        
        $em->persist($user);
        $em->flush();
        
        // 1. Login to get JWT
        $client->jsonRequest('POST', '/api/v1/login_check', [
            'username' => $user->getEmail(), // JsonLogin uses username by default usually, check configuration
            'password' => 'password'
        ]);
        
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $jwt = $data['token'] ?? null;
        $this->assertNotNull($jwt, 'JWT token not found in login response');

        // 2. Create API Token using JWT
        $client->request(
            'POST', 
            '/api/v1/tokens', 
            [], 
            [], 
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $jwt],
            json_encode(['description' => 'Test Token'])
        );
        
        $this->assertResponseStatusCodeSame(201);
        $tokenData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $tokenData);
        $plainToken = $tokenData['token'];
        $tokenId = $tokenData['id'];

        // 3. List tokens using JWT - should see masked token
        $client->request(
            'GET', 
            '/api/v1/tokens', 
            [], 
            [], 
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $jwt]
        );
        $this->assertResponseIsSuccessful();
        $listData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $listData);
        
        // Check if list data matches structure (assuming it returns array of tokens)
        $firstToken = $listData[0];
        
        // Depending on serialization groups, 'token' might not be present or might be null?
        // In my Entity, I removed group from 'token', added 'maskedToken' accessor with group.
        // So 'token' key should NOT be present or be null/empty, and 'maskedToken' should be present.
        // Let's verify what Serializer does for `getMaskedToken`. It usually includes it as `maskedToken`.
        
        $this->assertArrayHasKey('maskedToken', $firstToken, 'Response: ' . json_encode($listData));
        $this->assertStringNotContainsString($plainToken, $firstToken['maskedToken']);
        $this->assertStringEndsWith(substr($plainToken, -4), $firstToken['maskedToken']);

        // 4. Use the API Token to access /api/v1/tokens (testing X-API-TOKEN auth)
        $client->request(
            'GET',
            '/api/v1/tokens',
            [],
            [],
            ['HTTP_X_API_TOKEN' => $plainToken]
        );
        
        $this->assertResponseIsSuccessful();
        $tokenAuthData = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $tokenAuthData);

        // 5. Delete token
        $client->request(
            'DELETE',
            '/api/v1/tokens/' . $tokenId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $jwt]
        );
        $this->assertResponseStatusCodeSame(204);

        // 6. Try to use deleted API Token
        $client->request(
            'GET',
            '/api/v1/tokens',
            [],
            [],
            ['HTTP_X_API_TOKEN' => $plainToken]
        );
        $this->assertResponseStatusCodeSame(401);
    }
}
