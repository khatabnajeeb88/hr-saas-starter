<?php

namespace App\Controller;

use App\Entity\EmploymentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employment-type')]
#[IsGranted('ROLE_USER')]
class EmploymentTypeController extends AbstractController
{
    #[Route('/create/ajax', name: 'app_employment_type_create_ajax', methods: ['POST'])]
    public function createAjax(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;

        if (!$name) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        $employmentType = new EmploymentType();
        $employmentType->setName($name);

        $entityManager->persist($employmentType);
        $entityManager->flush();

        return $this->json([
            'id' => $employmentType->getId(),
            'name' => $employmentType->getName(),
        ]);
    }
}
