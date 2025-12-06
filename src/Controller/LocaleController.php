<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function index(Request $request): Response
    {
        // Redirect to default locale (en)
        return $this->redirectToRoute('app_dashboard', ['_locale' => 'ar']);
    }
}
