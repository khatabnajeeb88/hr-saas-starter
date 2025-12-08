<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SocialLoginService;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class LinkedInAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private $socialLoginService;
    private $entityManager;
    private $router;

    public function __construct(SocialLoginService $socialLoginService, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->socialLoginService = $socialLoginService;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_linkedin_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->socialLoginService->getClient('linkedin');
        if (!$client) {
             throw new AuthenticationException("LinkedIn login is disabled.");
        }
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var LinkedInResourceOwner $linkedinUser */
                $linkedinUser = $client->fetchUserFromToken($accessToken);

                $email = $linkedinUser->getEmail();

                // 1) have they logged in with LinkedIn before?
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['linkedinId' => $linkedinUser->getId()]);

                if ($existingUser) {
                    return $existingUser;
                }

                // 2) do we have a matching user by email?
                $user = null;
                if ($email) {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                }

                // 3) Maybe you just want to "register" them by creating
                // a User object
                if (!$user) {
                    if (!$email) {
                        throw new AuthenticationException("Your LinkedIn account doesn't have a public email address.");
                    }
                    $user = new User();
                    $user->setEmail($email);
                    $user->setName($linkedinUser->getFirstName() . ' ' . $linkedinUser->getLastName());
                    $user->setPassword(bin2hex(random_bytes(32))); 
                }

                // 4) Update the user
                $user->setLinkedinId($linkedinUser->getId());
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->router->generate('app_dashboard');

        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/login',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
