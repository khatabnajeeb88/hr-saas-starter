<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FlyonUITestController extends AbstractController
{
    #[Route('/flyonui-test', name: 'app_flyonui_test')]
    public function index(): Response
    {
        return $this->render('flyonui_test.html.twig');
    }
}
