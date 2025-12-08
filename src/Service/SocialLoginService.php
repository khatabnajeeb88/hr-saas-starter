<?php

namespace App\Service;

use App\Entity\SocialProvider;
use App\Repository\SocialProviderRepository;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Client\Provider\GithubClient;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Client\Provider\LinkedInClient;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\LinkedIn;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SocialLoginService
{
    private $socialProviderRepository;
    private $requestStack;
    private $urlGenerator;

    public function __construct(
        SocialProviderRepository $socialProviderRepository,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->socialProviderRepository = $socialProviderRepository;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    public function getClient(string $providerName): ?OAuth2Client
    {
        $provider = $this->socialProviderRepository->findOneBy(['name' => $providerName, 'isEnabled' => true]);

        if (!$provider) {
            return null;
        }

        switch ($providerName) {
            case 'google':
                return $this->createGoogleClient($provider);
            case 'github':
                return $this->createGithubClient($provider);
            case 'linkedin':
                return $this->createLinkedInClient($provider);
            default:
                return null;
        }
    }

    private function createGoogleClient(SocialProvider $provider): GoogleClient
    {
        $client = new Google([
            'clientId'     => $provider->getClientId(),
            'clientSecret' => $provider->getClientSecret(),
            'redirectUri'  => $this->urlGenerator->generate('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new GoogleClient($client, $this->requestStack);
    }

    private function createGithubClient(SocialProvider $provider): GithubClient
    {
        $client = new Github([
            'clientId'     => $provider->getClientId(),
            'clientSecret' => $provider->getClientSecret(),
            'redirectUri'  => $this->urlGenerator->generate('connect_github_check', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new GithubClient($client, $this->requestStack);
    }

    private function createLinkedInClient(SocialProvider $provider): LinkedInClient
    {
        $client = new LinkedIn([
            'clientId'     => $provider->getClientId(),
            'clientSecret' => $provider->getClientSecret(),
            'redirectUri'  => $this->urlGenerator->generate('connect_linkedin_check', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new LinkedInClient($client, $this->requestStack);
    }

    public function getEnabledProviders(): array
    {
        return $this->socialProviderRepository->findBy(['isEnabled' => true]);
    }
}
