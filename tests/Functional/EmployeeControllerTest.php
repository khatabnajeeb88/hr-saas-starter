<?php

namespace App\Tests\Functional;

use App\Entity\Employee;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Repository\EmployeeRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EmployeeControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $user;
    private $team;
    private $department;
    private $employmentType;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        // Create User
        $this->user = new User();
        $this->user->setEmail('employee_test_user_' . uniqid() . '@example.com');
        $this->user->setPassword($hasher->hashPassword($this->user, 'password'));
        $this->user->setName('Test User');
        $this->entityManager->persist($this->user);

        // Create Team
        $this->team = new Team();
        $this->team->setName('Test Team');
        $this->team->setSlug('test-team-' . uniqid());
        $this->team->setOwner($this->user);
        $this->team->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($this->team);

        // Create Team Member
        $teamMember = new TeamMember();
        $teamMember->setUser($this->user);
        $teamMember->setTeam($this->team);
        $teamMember->setRole(TeamMember::ROLE_OWNER);
        $this->entityManager->persist($teamMember);

        // IMPORTANT: Add member to collections to ensure consistency if entities are reused in memory
        $this->team->addMember($teamMember);
        $this->user->addTeamMember($teamMember);

        // Create Department
        $this->department = new \App\Entity\Department();
        $this->department->setName('Test Department ' . uniqid());
        $this->entityManager->persist($this->department);

        // Create Employment Type
        $this->employmentType = new \App\Entity\EmploymentType();
        $this->employmentType->setName('Full Time ' . uniqid());
        $this->entityManager->persist($this->employmentType);

        $this->entityManager->flush();
    }

    public function testIndex(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/en/employee/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Employees');
    }

    public function testNew(): void
    {
        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', '/en/employee/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'New Employee');

        $email = 'john.doe.' . uniqid() . '@example.com';

        $form = $crawler->selectButton('Save')->form();
        $form['employee[firstName]'] = 'John';
        $form['employee[lastName]'] = 'Doe';
        $form['employee[email]'] = $email;
        $form['employee[mobile]'] = '1234567890';
        $form['employee[jobTitle]'] = 'Developer';
        $form['employee[department]'] = $this->department->getId();
        $form['employee[employmentType]'] = $this->employmentType->getId();
        $form['employee[workType]'] = 'remote';
        $form['employee[shift]'] = 'regular';
        $form['employee[joiningDate]'] = (new \DateTime())->format('Y-m-d');
        $form['employee[basicSalary]'] = '5000.00';
        $form['employee[employmentStatus]'] = 'active';

        $this->client->submit($form);



        $this->assertResponseRedirects('/en/employee/');

        // Verify it was created
        $employee = $this->entityManager->getRepository(Employee::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($employee);
        $this->assertEquals('John', $employee->getFirstName());
        $this->assertEquals($this->team->getId(), $employee->getTeam()->getId());
    }

    public function testNewEmployeeContractCreation(): void
    {
        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', '/en/employee/new');

        $email = 'contract.test.' . uniqid() . '@example.com';

        $form = $crawler->selectButton('Save')->form();
        $form['employee[firstName]'] = 'Contract';
        $form['employee[lastName]'] = 'Test';
        $form['employee[email]'] = $email;
        $form['employee[mobile]'] = '9876543210';
        $form['employee[jobTitle]'] = 'Contractor';
        $form['employee[department]'] = $this->department->getId();
        $form['employee[employmentType]'] = $this->employmentType->getId();
        $form['employee[workType]'] = 'office';
        $form['employee[shift]'] = 'regular';
        $form['employee[joiningDate]'] = (new \DateTime())->format('Y-m-d');
        $form['employee[basicSalary]'] = '5000.00';

        $this->client->submit($form);

        $employee = $this->entityManager->getRepository(Employee::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($employee);

        // Check for contract
        $contracts = $employee->getContracts();
        $this->assertCount(1, $contracts);
        
        $contract = $contracts->first();
        $this->assertEquals('5000.00', $contract->getBasicSalary());
        $this->assertEquals(\App\Entity\Contract::STATUS_DRAFT, $contract->getStatus());
    }

    public function testEdit(): void
    {
        // Reload team to ensure it's managed
        $team = $this->entityManager->getRepository(Team::class)->find($this->team->getId());

        // Create an employee first
        $employee = new Employee();
        $employee->setFirstName('Jane');
        $employee->setLastName('Doe');
        $employee->setEmail('jane.doe.' . uniqid() . '@example.com');
        $employee->setMobile('5555555555');
        $employee->setTeam($team);
        $employee->setDepartment($this->department);
        $employee->setEmploymentType($this->employmentType);
        $employee->setJobTitle('Tester');
        $employee->setWorkType('hybrid');
        $employee->setShift('regular');
        $employee->setBasicSalary('4000.00');
        $employee->setJoiningDate(new \DateTimeImmutable());
        $employee->setJoinedAt(new \DateTimeImmutable());
        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', '/en/employee/' . $employee->getId() . '/edit');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Update')->form();
        $form['employee[firstName]'] = 'Jane Updated';

        $this->client->submit($form);

        $this->assertResponseRedirects('/en/employee/');

        // Refresh employee
        $this->entityManager->clear(); // Clear identity map to force fetch
        $employee = $this->entityManager->getRepository(Employee::class)->find($employee->getId());
        $this->assertEquals('Jane Updated', $employee->getFirstName());
    }

    public function testDelete(): void
    {
        // Reload team to ensure it's managed
        $team = $this->entityManager->getRepository(Team::class)->find($this->team->getId());

        // Create an employee first
        $employee = new Employee();
        $employee->setFirstName('To Delete');
        $employee->setLastName('User');
        $employee->setEmail('delete.me@example.com');
        $employee->setMobile('1111111111');
        $employee->setTeam($team);
        $employee->setDepartment($this->department);
        $employee->setEmploymentType($this->employmentType);
        $employee->setJobTitle('Disposable');
        $employee->setWorkType('office');
        $employee->setShift('night');
        $employee->setBasicSalary('3000.00');
        $employee->setJoiningDate(new \DateTimeImmutable());
        $employee->setJoinedAt(new \DateTimeImmutable());
        $this->entityManager->persist($employee);
        $this->entityManager->flush();

        $employeeId = $employee->getId();

        $this->client->loginUser($this->user);
        
        // Simulating the delete button click which is inside a form
        // We can request the index page and submit the form associated with the employee
        $crawler = $this->client->request('GET', '/en/employee/');
        $this->assertResponseIsSuccessful();
        
        // Find the form for this specific employee. 
        // The form action should be /en/employee/{id}
        $form = $crawler->filter('form[action="/en/employee/' . $employeeId . '"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/en/employee/');
        
        // Clear EntityManager to fetch fresh data
        $this->entityManager->clear();
        
        $deletedEmployee = $this->entityManager->getRepository(Employee::class)->find($employeeId);
        $this->assertNull($deletedEmployee);
    }
    
    public function testProfileImageUpload(): void
    {
        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', '/en/employee/new');

        $email = 'image.test.' . uniqid() . '@example.com';
        
        // Create a temporary dummy image (1x1 pixel JPEG)
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        $base64Jpg = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
        file_put_contents($tempFile, base64_decode($base64Jpg));
        $newTempFile = $tempFile . '.jpg';
        rename($tempFile, $newTempFile);

        $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
            $newTempFile,
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        $form = $crawler->selectButton('Save')->form();
        $form['employee[firstName]'] = 'Image';
        $form['employee[lastName]'] = 'Test';
        $form['employee[email]'] = $email;
        $form['employee[mobile]'] = '2222222222';
        $form['employee[jobTitle]'] = 'Model';
        $form['employee[department]'] = $this->department->getId();
        $form['employee[employmentType]'] = $this->employmentType->getId();
        $form['employee[workType]'] = 'remote';
        $form['employee[shift]'] = 'regular';
        $form['employee[joiningDate]'] = (new \DateTime())->format('Y-m-d');
        $form['employee[basicSalary]'] = '6000.00';
        $form['employee[profileImage]'] = $uploadedFile;

        $this->client->submit($form);

        $employee = $this->entityManager->getRepository(Employee::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($employee);
        $this->assertNotNull($employee->getProfileImage());
        
        // Verify file exists
        $uploadDir = static::getContainer()->getParameter('kernel.project_dir') . '/public/uploads/photos';
        $this->assertFileExists($uploadDir . '/' . $employee->getProfileImage());
        
        // Cleanup uploaded file
        if (file_exists($uploadDir . '/' . $employee->getProfileImage())) {
            unlink($uploadDir . '/' . $employee->getProfileImage());
        }
        // Cleanup temp file
        if (file_exists($newTempFile)) {
            unlink($newTempFile);
        }
    }

    public function testManagerSelfExclusion(): void
    {
        // Reload team to ensure it's managed
        $team = $this->entityManager->getRepository(Team::class)->find($this->team->getId());

        // Create an employee
        $employee = new Employee();
        $employee->setFirstName('Manager');
        $employee->setLastName('Candidate');
        $employee->setEmail('manager.' . uniqid() . '@example.com');
        $employee->setMobile('3333333333');
        $employee->setTeam($team);
        $employee->setDepartment($this->department);
        $employee->setEmploymentType($this->employmentType);
        $employee->setJobTitle('Manager');
        $employee->setWorkType('office');
        $employee->setShift('regular');
        $employee->setBasicSalary('8000.00');
        $employee->setJoiningDate(new \DateTimeImmutable());
        $employee->setJoinedAt(new \DateTimeImmutable());
        $this->entityManager->persist($employee);
        
        // Create another employee to be a potential manager
        $otherEmployee = new Employee();
        $otherEmployee->setFirstName('Other');
        $otherEmployee->setLastName('Employee');
        $otherEmployee->setEmail('other.' . uniqid() . '@example.com');
        $otherEmployee->setMobile('4444444444');
        $otherEmployee->setTeam($team);
        $otherEmployee->setDepartment($this->department);
        $otherEmployee->setEmploymentType($this->employmentType);
        $otherEmployee->setJobTitle('Subordinate');
        $otherEmployee->setWorkType('office');
        $otherEmployee->setShift('regular');
        $otherEmployee->setBasicSalary('4000.00');
        $otherEmployee->setJoiningDate(new \DateTimeImmutable());
        $otherEmployee->setJoinedAt(new \DateTimeImmutable());
        $this->entityManager->persist($otherEmployee);
        
        $this->entityManager->flush();

        $this->client->loginUser($this->user);
        
        // Go to edit page of the first employee
        $crawler = $this->client->request('GET', '/en/employee/' . $employee->getId() . '/edit');
        
        $this->assertResponseIsSuccessful();
        
        // Check options in the manager select
        $managerSelect = $crawler->filter('select[name="employee[manager]"]');
        
        // The option for the employee themselves should NOT exist
        $selfOption = $managerSelect->filter('option[value="' . $employee->getId() . '"]');
        $this->assertCount(0, $selfOption, 'Self should not be in manager list');
        
        // The option for the other employee SHOULD exist
        $otherOption = $managerSelect->filter('option[value="' . $otherEmployee->getId() . '"]');
        $this->assertCount(1, $otherOption, 'Other employee should be in manager list');
    }

    public function testAccessDeniedForOtherTeam(): void
    {
        // Create another user and team
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $otherUser = new User();
        $otherUser->setEmail('other_user_' . uniqid() . '@example.com');
        $otherUser->setPassword($hasher->hashPassword($otherUser, 'password'));
        $otherUser->setName('Other User');
        $this->entityManager->persist($otherUser);
        
        $otherTeam = new Team();
        $otherTeam->setName('Other Team');
        $otherTeam->setSlug('other-team-' . uniqid());
        $otherTeam->setOwner($otherUser);
        $otherTeam->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($otherTeam);
        
        $otherTeamMember = new TeamMember();
        $otherTeamMember->setUser($otherUser);
        $otherTeamMember->setTeam($otherTeam);
        $otherTeamMember->setRole(TeamMember::ROLE_OWNER);
        $this->entityManager->persist($otherTeamMember);
        
        // Create employee in original team
        $employee = new Employee();
        $employee->setFirstName('Original');
        $employee->setLastName('Team Employee');
        $employee->setTeam($this->team);
        $employee->setMobile('6666666666');
        $employee->setDepartment($this->department);
        $employee->setEmploymentType($this->employmentType);
        $employee->setJobTitle('Protected');
        $employee->setWorkType('office');
        $employee->setShift('regular');
        $employee->setBasicSalary('5500.00');
        $employee->setJoiningDate(new \DateTimeImmutable());
        $employee->setJoinedAt(new \DateTimeImmutable());
        $this->entityManager->persist($employee);
        
        $this->entityManager->flush();
        
        // Login as other user
        $this->client->loginUser($otherUser);
        
        // Try to access employee from different team
        $this->client->request('GET', '/en/employee/' . $employee->getId());
        $this->assertResponseStatusCodeSame(403);
        
        $this->client->request('GET', '/en/employee/' . $employee->getId() . '/edit');
        $this->assertResponseStatusCodeSame(403);
    }

}
