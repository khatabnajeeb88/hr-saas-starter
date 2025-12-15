<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Form\EmployeeType;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee')]
#[IsGranted('ROLE_USER')]
class EmployeeController extends AbstractController
{
    #[Route('/', name: 'app_employee_index', methods: ['GET'])]
    public function index(Request $request, EmployeeRepository $employeeRepository): Response
    {
        // Default to first team for now if not specified (TODO: Add team selector support)
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Create a query builder instead of fetching all result
        // TODO: Refine this to filter by specific team from context
        $queryBuilder = $employeeRepository->createQueryBuilder('e')
            ->orderBy('e.joinedAt', 'DESC');

        $adapter = new \Pagerfanta\Doctrine\ORM\QueryAdapter($queryBuilder);
        $pagerfanta = new \Pagerfanta\Pagerfanta($adapter);

        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);

        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $this->render('employee/index.html.twig', [
            'pager' => $pagerfanta,
        ]);
    }

    #[Route('/new', name: 'app_employee_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $employee = new Employee();
        
        // Set the team! We MUST set the team.
        // For MVP, let's set it to the User's first team member's team?
        // Or do we have a way to know the current team?
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $firstTeamMember = $user->getTeamMembers()->first();
        if ($firstTeamMember) {
            $employee->setTeam($firstTeamMember->getTeam());
        } else {
             $this->addFlash('error', 'You must belong to a team to create employees.');
             return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($employee);
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employee/new.html.twig', [
            'employee' => $employee,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employee_show', methods: ['GET'])]
    public function show(Employee $employee): Response
    {
        // Security check: ensure employee belongs to one of user's teams
        $user = $this->getUser();
        if (!$employee->getTeam()->hasMember($user)) {
             throw $this->createAccessDeniedException();
        }

        return $this->render('employee/show.html.twig', [
            'employee' => $employee,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_employee_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        // Security check
        $user = $this->getUser();
        if (!$employee->getTeam()->hasMember($user)) {
             throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('employee/edit.html.twig', [
            'employee' => $employee,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_employee_delete', methods: ['POST'])]
    public function delete(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        // Security check
        $user = $this->getUser();
        if (!$employee->getTeam()->hasMember($user)) {
             throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$employee->getId(), $request->request->get('_token'))) {
            $entityManager->remove($employee);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
    }
}
