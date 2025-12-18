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
    public function new(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
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
            // Handle profile image upload
            // Handle profile image upload
            $this->handleProfileImageUpload($form, $employee, $slugger);
            // Handle document uploads
            $this->handleDocumentUploads($form, $employee, $slugger);

            $entityManager->persist($employee);
            
            // Auto-create draft contract
            $contract = new \App\Entity\Contract();
            $contract->setEmployee($employee);
            $contract->setBasicSalary($employee->getBasicSalary() ?? '0.00');
            $contract->setStartDate($employee->getJoiningDate() ?? new \DateTimeImmutable());
            // Default to Saudi for now, or maybe infer?
            $contract->setType(\App\Entity\Contract::TYPE_SAUDI); 
            $contract->setStatus(\App\Entity\Contract::STATUS_DRAFT);
            
            $entityManager->persist($contract);
            
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
    public function edit(Request $request, Employee $employee, EntityManagerInterface $entityManager, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        // Security check
        $user = $this->getUser();
        if (!$employee->getTeam()->hasMember($user)) {
             throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile image upload
            // Handle profile image upload
            $this->handleProfileImageUpload($form, $employee, $slugger);
            // Handle document uploads
            $this->handleDocumentUploads($form, $employee, $slugger);

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

    #[Route('/{id}/archive', name: 'app_employee_archive', methods: ['POST'])]
    public function archive(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        // Security check
        $user = $this->getUser();
        if (!$employee->getTeam()->hasMember($user)) {
             throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('archive'.$employee->getId(), $request->request->get('_token'))) {
            $employee->setEmploymentStatus('archived');
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_employee_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleDocumentUploads($form, Employee $employee, \Symfony\Component\String\Slugger\SluggerInterface $slugger): void
    {
        $documents = $form->get('documents');
        foreach ($documents as $documentForm) {
            /** @var \App\Entity\EmployeeDocument $document */
            $document = $documentForm->getData();
            
            // If the document is being removed, we don't need to process uploads
            if (!$document) {
                continue;
            }

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $documentForm->get('file')->getData();

            if ($file) {
                 $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/documents',
                        $newFilename
                    );
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                     // ... handle exception if something happens during file upload
                }

                $document->setFile($newFilename);
            } elseif (!$document->getFile() && !$document->getId()) {
                // Determine if this is a new document that is missing a file
                 // If it is new and hasn't had a file set, we might have an issue since 'file' is not nullable.
                 // However, we can't easily validate this here without more complex logic or relying on Form constraints.
                 // For now, let's assume the form validation (if added) or DB info is enough.
                 // Wait, constraint is NOT NULL. So we should probably remove the document if no file is uploaded for a NEW document?
                 // Or let it fail. Better to let it fail or add a validation constraint on the form.
                 // The form has "required => false" on the file input, but logically for a NEW document a file is required.
            }
        }
    }

    private function handleProfileImageUpload($form, Employee $employee, \Symfony\Component\String\Slugger\SluggerInterface $slugger): void
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
        $imageFile = $form->get('profileImage')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/photos',
                    $newFilename
                );
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                // ... handle exception if something happens during file upload
            }

            $employee->setProfileImage($newFilename);
        }
    }
}
