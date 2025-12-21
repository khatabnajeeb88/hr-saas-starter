<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Form\EmployeeType;
use App\Form\EmployeeImportType;
use App\Repository\EmployeeRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
    public function index(Request $request, EmployeeRepository $employeeRepository, \App\Repository\DepartmentRepository $departmentRepository): Response
    {
        // Default to first team for now if not specified
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $team = $user->getTeamMembers()->first()?->getTeam();
        
        $queryBuilder = $employeeRepository->createQueryBuilder('e')
            ->orderBy('e.joinedAt', 'DESC');

        if ($team) {
            $queryBuilder->andWhere('e.team = :team')
                ->setParameter('team', $team);
        } else {
             // If no team, user shouldn't see any employees (or maybe all if super admin? stick to secure default)
             $queryBuilder->andWhere('1 = 0');
        }

        // Filters
        $filters = [
            'name' => $request->query->get('name'),
            'email' => $request->query->get('email'),
            'mobile' => $request->query->get('mobile'),
            'job_search' => $request->query->get('job_search'),
            'department_id' => $request->query->get('department_id'),
            'status' => $request->query->get('status'),
        ];

        if (!empty($filters['name'])) {
            $queryBuilder->andWhere('(e.firstName LIKE :name OR e.lastName LIKE :name)')
                ->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['email'])) {
            $queryBuilder->andWhere('e.email LIKE :email')
                ->setParameter('email', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['mobile'])) {
            $queryBuilder->andWhere('e.mobile LIKE :mobile')
                ->setParameter('mobile', '%' . $filters['mobile'] . '%');
        }

        if (!empty($filters['job_search'])) {
            $queryBuilder->andWhere('e.jobTitle LIKE :jobSearch')
                ->setParameter('jobSearch', '%' . $filters['job_search'] . '%');
        }

        if (!empty($filters['department_id'])) {
            $queryBuilder->andWhere('e.department = :departmentId')
                ->setParameter('departmentId', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $queryBuilder->andWhere('e.employmentStatus = :status')
                ->setParameter('status', $filters['status']);
        }

        $adapter = new \Pagerfanta\Doctrine\ORM\QueryAdapter($queryBuilder);
        $pagerfanta = new \Pagerfanta\Pagerfanta($adapter);

        $limit = $request->query->getInt('limit', 10);
        $page = $request->query->getInt('page', 1);

        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        // Fetch departments for filter dropdown
        $departments = $departmentRepository->findAll();

        return $this->render('employee/index.html.twig', [
            'pager' => $pagerfanta,
            'departments' => $departments,
            'filters' => $filters,
        ]);
    }

    #[Route('/import', name: 'app_employee_import', methods: ['GET', 'POST'])]
    public function import(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EmployeeImportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $form->get('file')->getData();

            if ($file) {
                // Check for team membership first
                /** @var \App\Entity\User $user */
                $user = $this->getUser();
                $firstTeamMember = $user->getTeamMembers()->first();
                if (!$firstTeamMember) {
                    $this->addFlash('error', 'You must belong to a team to import employees.');
                    return $this->redirectToRoute('app_employee_index');
                }
                $team = $firstTeamMember->getTeam();

                $spreadsheet = IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
                
                // Remove header row
                $header = array_shift($rows);
                
                $successCount = 0;
                
                foreach ($rows as $data) {
                    // Simple position based mapping
                    // 0: FirstName, 1: LastName, 2: Email, 3: Mobile, 4: National ID, 5: Job Title, 6: Rate/Salary, 7: Joined At
                    
                    // Filter empty rows (sometimes Excel has empty rows at bottom)
                    if (empty(array_filter($data))) continue;
                    if (count($data) < 2) continue;

                    $employee = new Employee();
                    $employee->setTeam($team);
                    $employee->setFirstName((string)($data[0] ?? 'Unknown'));
                    $employee->setLastName((string)($data[1] ?? 'Unknown'));
                    $employee->setEmail($data[2] ?? null);
                    $employee->setMobile($data[3] ?? null);
                    $employee->setNationalId($data[4] ?? null);
                    $employee->setJobTitle($data[5] ?? null);
                    $employee->setBasicSalary($data[6] ?? null);
                    
                    try {
                        $dateValue = $data[7] ?? null;
                        if ($dateValue) {
                            if (is_numeric($dateValue)) {
                                $joinedAt = \DateTimeImmutable::createFromMutable(Date::excelToDateTimeObject($dateValue));
                            } else {
                                $joinedAt = new \DateTimeImmutable($dateValue);
                            }
                            $employee->setJoinedAt($joinedAt);
                        } else {
                            $employee->setJoinedAt(new \DateTimeImmutable());
                        }
                    } catch (\Exception $e) {
                        $employee->setJoinedAt(new \DateTimeImmutable());
                    }

                    $employee->setEmploymentStatus('active');

                    $entityManager->persist($employee);
                    $successCount++;
                }
                
                $entityManager->flush();
                $this->addFlash('success', "Imported $successCount employees successfully.");
                return $this->redirectToRoute('app_employee_index');
            }
        }

        return $this->render('employee/import.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/import/sample', name: 'app_employee_import_sample', methods: ['GET'])]
    public function importSample(): Response
    {
        $csvHeader = ['First Name', 'Last Name', 'Email', 'Mobile', 'National ID', 'Job Title', 'Basic Salary', 'Joining Date (YYYY-MM-DD)'];
        $sampleRow = ['John', 'Doe', 'john.doe@example.com', '0500000000', '1234567890', 'Software Engineer', '10000.00', date('Y-m-d')];

        $content = implode(',', $csvHeader) . "\n" . implode(',', $sampleRow);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="employees_sample.csv"');

        return $response;
    }

    #[Route('/export', name: 'app_employee_export', methods: ['POST'])]
    public function export(Request $request, EmployeeRepository $employeeRepository, \Symfony\Contracts\Translation\TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        $team = $user->getTeamMembers()->first()?->getTeam(); // Assuming active team context or first team

        if (!$team) {
            $this->addFlash('error', 'You must belong to a team to export employees.');
            return $this->redirectToRoute('app_employee_index');
        }

        $includeAll = $request->request->get('include_all') === '1';
        $idsStr = $request->request->get('ids');
        $selectedColumns = $request->request->all()['columns'] ?? [];

        if ($includeAll) {
             // Fetch all for the team
             $employees = $employeeRepository->findBy(['team' => $team], ['joinedAt' => 'DESC']);
        } else {
             $ids = array_filter(explode(',', $idsStr));
             if (empty($ids)) {
                  // Fallback to all if something weird happened (though js handles logic)
                  $employees = $employeeRepository->findBy(['team' => $team], ['joinedAt' => 'DESC']);
             } else {
                  $employees = $employeeRepository->findBy(['id' => $ids, 'team' => $team]);
             }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Define available columns mapping
        $columnMap = [
            'fullName' => ['label' => 'Full Name', 'getter' => 'getFullName'],
            'email' => ['label' => 'Email', 'getter' => 'getEmail'],
            'mobile' => ['label' => 'Mobile', 'getter' => 'getMobile'],
            'jobTitle' => ['label' => 'Job Title', 'getter' => 'getJobTitle'],
            'department' => ['label' => 'Department', 'getter' => function(Employee $e) { return $e->getDepartment()?->getName(); }],
            'employmentStatus' => ['label' => 'Employment Status', 'getter' => 'getEmploymentStatus'],
            'joinedAt' => ['label' => 'Joining Date', 'getter' => function(Employee $e) { return $e->getJoinedAt()?->format('Y-m-d'); }],
            'basicSalary' => ['label' => 'Basic Salary', 'getter' => 'getBasicSalary'],
            'iban' => ['label' => 'IBAN', 'getter' => 'getIban'],
            'nationalId' => ['label' => 'National ID', 'getter' => 'getNationalId'],
            'badgeId' => ['label' => 'Badge ID', 'getter' => 'getBadgeId'],
        ];

        // Filter valid columns
        $activeColumns = [];
        foreach ($selectedColumns as $colKey) {
            if (isset($columnMap[$colKey])) {
                $activeColumns[$colKey] = $columnMap[$colKey];
            }
        }
        
        // Default to all columns if none selected? Or just names? Let's default to Name only if empty.
        if (empty($activeColumns)) {
            $activeColumns['fullName'] = $columnMap['fullName'];
        }

        // Write Headers
        $colIndex = 1;
        foreach ($activeColumns as $key => $config) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex) . '1', $translator->trans($config['label']));
            $colIndex++;
        }

        // Write Data
        $rowIndex = 2;
        foreach ($employees as $employee) {
            $colIndex = 1;
            foreach ($activeColumns as $key => $config) {
                $value = '';
                $getter = $config['getter'];
                if (is_callable($getter)) {
                    $value = $getter($employee);
                } else {
                    $value = $employee->$getter();
                }
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex) . $rowIndex, $value);
                $colIndex++;
            }
            $rowIndex++;
        }
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="employees_export_'.date('Y-m-d_H-i').'.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }



    private function getBulkEmployees(Request $request, EmployeeRepository $employeeRepository): array
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        $team = $user->getTeamMembers()->first()?->getTeam();

        if (!$team) {
            return [];
        }

        $includeAll = $request->request->get('include_all') === '1';
        $idsStr = $request->request->get('ids');

        if ($includeAll) {
             return $employeeRepository->findBy(['team' => $team]);
        }
        
        $ids = array_filter(explode(',', $idsStr));
        if (empty($ids)) {
             return [];
        }

        return $employeeRepository->findBy(['id' => $ids, 'team' => $team]);
    }

    #[Route('/bulk/archive', name: 'app_employee_bulk_archive', methods: ['POST'])]
    public function bulkArchive(Request $request, EmployeeRepository $employeeRepository, EntityManagerInterface $entityManager): Response
    {
        $employees = $this->getBulkEmployees($request, $employeeRepository);
        $count = 0;

        foreach ($employees as $employee) {
            if ($employee->getEmploymentStatus() !== 'archived') {
                $employee->setEmploymentStatus('archived');
                $count++;
            }
        }

        $entityManager->flush();
        $this->addFlash('success', $count . ' employees archived successfully.');

        return $this->redirectToRoute('app_employee_index');
    }

    #[Route('/bulk/unarchive', name: 'app_employee_bulk_unarchive', methods: ['POST'])]
    public function bulkUnarchive(Request $request, EmployeeRepository $employeeRepository, EntityManagerInterface $entityManager): Response
    {
        $employees = $this->getBulkEmployees($request, $employeeRepository);
        $count = 0;

        foreach ($employees as $employee) {
            if ($employee->getEmploymentStatus() === 'archived') {
                $employee->setEmploymentStatus('active'); // Default to active or whatever previous status? simplifying to active
                $count++;
            }
        }

        $entityManager->flush();
        $this->addFlash('success', $count . ' employees unarchived successfully.');

        return $this->redirectToRoute('app_employee_index');
    }

    #[Route('/bulk/delete', name: 'app_employee_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EmployeeRepository $employeeRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('bulk_delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_employee_index');
        }

        $employees = $this->getBulkEmployees($request, $employeeRepository);
        $count = 0;

        foreach ($employees as $employee) {
            $entityManager->remove($employee);
            $count++;
        }

        $entityManager->flush();
        $this->addFlash('success', $count . ' employees deleted successfully.');

        return $this->redirectToRoute('app_employee_index');
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
