<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\User;
use App\Form\EmployeePortalProfileType;
use App\Form\EmployeeDocumentType;
use App\Entity\EmployeeDocument;
use App\Entity\Contract;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/portal')]
#[IsGranted('ROLE_EMP')]
class EmployeePortalController extends AbstractController
{
    #[Route('', name: 'app_employee_portal_dashboard')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $employee = $user->getEmployee();

        if (!$employee) {
            throw $this->createAccessDeniedException('No employee record associated with this user.');
        }

        return $this->render('portal/dashboard/index.html.twig', [
            'employee' => $employee,
        ]);
    }

    #[Route('/profile', name: 'app_employee_portal_profile')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $employee = $user->getEmployee();
        
        return $this->render('portal/profile/view.html.twig', [
            'employee' => $employee,
        ]);
    }

    #[Route('/profile/edit', name: 'app_employee_portal_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $employee = $user->getEmployee();
        
        $form = $this->createForm(EmployeePortalProfileType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleProfileImageUpload($form, $employee, $slugger);
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('app_employee_portal_profile', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('portal/profile/edit.html.twig', [
            'employee' => $employee,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/documents', name: 'app_employee_portal_documents')]
    public function documents(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $employee = $user->getEmployee();
        
        // Create upload form
        $document = new EmployeeDocument();
        $document->setEmployee($employee);
        $form = $this->createForm(EmployeeDocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $form->get('file')->getData();
            if ($file) {
                // In a real app, use a service for file uploads
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads/documents',
                    $fileName
                );
                $document->setFile($fileName);
            }

            $entityManager->persist($document);
            $entityManager->flush();

            $this->addFlash('success', 'Document uploaded successfully.');
            return $this->redirectToRoute('app_employee_portal_documents');
        }

        return $this->render('portal/documents/index.html.twig', [
            'employee' => $employee,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/documents/{id}/download', name: 'app_employee_portal_documents_download')]
    public function downloadDocument(int $id, EntityManagerInterface $entityManager): Response
    {
         /** @var User $user */
        $user = $this->getUser();
        $employee = $user->getEmployee();

        $document = $entityManager->getRepository(EmployeeDocument::class)->find($id);

        if (!$document || $document->getEmployee() !== $employee) {
            throw $this->createAccessDeniedException('You are not authorized to view this document.');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/documents/' . $document->getFile();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist.');
        }

        return $this->file($filePath);
    }

    #[Route('/contract/{id}/download', name: 'app_employee_portal_contract_download')]
    public function downloadContract(int $id, EntityManagerInterface $entityManager): Response
    {
         /** @var User $user */
        $user = $this->getUser();
        $employee = $user->getEmployee();

        $contract = $entityManager->getRepository(Contract::class)->find($id);

        if (!$contract || $contract->getEmployee() !== $employee) {
             throw $this->createAccessDeniedException('You are not authorized to view this contract.');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/contracts/' . $contract->getFile();
        
        if (!$contract->getFile() || !file_exists($filePath)) {
            throw $this->createNotFoundException('The contract file does not exist.');
        }

        return $this->file($filePath);
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
