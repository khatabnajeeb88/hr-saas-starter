<?php

namespace App\Twig;

use App\Service\SocialLoginService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SocialLoginExtension extends AbstractExtension
{
    private $socialLoginService;

    public function __construct(SocialLoginService $socialLoginService)
    {
        $this->socialLoginService = $socialLoginService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('social_providers', [$this, 'getSocialProviders']),
        ];
    }

    public function getSocialProviders(): array
    {
        return $this->socialLoginService->getEnabledProviders();
    }
}
