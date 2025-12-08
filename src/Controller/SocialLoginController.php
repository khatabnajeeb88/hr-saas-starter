<?php

namespace App\Controller;

use App\Service\SocialLoginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class SocialLoginController extends AbstractController
{
    #[Route(path: '/connect/google', name: 'connect_google')]
    public function connectGoogle(SocialLoginService $socialLoginService): RedirectResponse
    {
        $client = $socialLoginService->getClient('google');
        if (!$client) {
            throw $this->createNotFoundException('Google login is not enabled.');
        }

        return $client->redirect(['email', 'profile'], []);
    }

    #[Route(path: '/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck()
    {
        // This method is just a blank placeholder; the
        // authenticator will intercept the request.
    }

    #[Route(path: '/connect/github', name: 'connect_github')]
    public function connectGithub(SocialLoginService $socialLoginService): RedirectResponse
    {
        $client = $socialLoginService->getClient('github');
        if (!$client) {
            throw $this->createNotFoundException('Github login is not enabled.');
        }

        return $client->redirect(['user:email'], []);
    }

    #[Route(path: '/connect/github/check', name: 'connect_github_check')]
    public function connectGithubCheck()
    {
        // This method is just a blank placeholder; the
        // authenticator will intercept the request.
    }

    #[Route(path: '/connect/linkedin', name: 'connect_linkedin')]
    public function connectLinkedin(SocialLoginService $socialLoginService): RedirectResponse
    {
        $client = $socialLoginService->getClient('linkedin');
        if (!$client) {
            throw $this->createNotFoundException('LinkedIn login is not enabled.');
        }

        return $client->redirect(['r_emailaddress', 'r_liteprofile'], []);
    }

    #[Route(path: '/connect/linkedin/check', name: 'connect_linkedin_check')]
    public function connectLinkedinCheck()
    {
        // This method is just a blank placeholder; the
        // authenticator will intercept the request.
    }
}
