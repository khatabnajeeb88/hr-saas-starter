<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MercureAuthorizationSubscriber implements EventSubscriberInterface
{
    private $jwtSecret;

    public function __construct(
        private Security $security,
        #[Autowire('%env(MERCURE_JWT_SECRET)%')]
        string $jwtSecret
    ) {
        $this->jwtSecret = $jwtSecret;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->jwtSecret)
        );

        $topics = [
            "https://example.com/users/{$user->getId()}/notifications"
        ];

        $token = $config->builder()
            ->withClaim('mercure', ['subscribe' => $topics])
            ->getToken($config->signer(), $config->signingKey());

        $cookie = Cookie::create(
            'mercureAuthorization',
            $token->toString(),
            0, // Session cookie
            '/',
            null, // Domain (null for current domain)
            false, // Secure (set to true in production with HTTPS)
            true, // HttpOnly
            false, // Raw
            Cookie::SAMESITE_LAX
        );

        $event->getResponse()->headers->setCookie($cookie);
    }
}
