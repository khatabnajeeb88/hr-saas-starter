<?php

namespace App\Controller;

use App\Entity\EmployeeTag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tag')]
#[IsGranted('ROLE_USER')]
class EmployeeTagController extends AbstractController
{
    #[Route('/create/ajax', name: 'app_tag_create_ajax', methods: ['POST'])]
    public function createAjax(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;

        if (!$name) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $tag = new EmployeeTag();
        $tag->setName($name);
        // Default color if needed, or allow it to be passed
        $tag->setColor('blue'); 

        $entityManager->persist($tag);
        $entityManager->flush();

        return $this->json([
            'id' => $tag->getId(),
            'name' => $tag->getName(),
        ]);
    }
}
