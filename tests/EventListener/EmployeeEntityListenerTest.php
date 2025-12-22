<?php

namespace App\Tests\EventListener;

use App\Entity\Employee;
use App\Entity\Team;
use App\Entity\User;
use App\EventListener\EmployeeEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EmployeeEntityListenerTest extends TestCase
{
    public function testPrePersistSyncsUserAndAssignsTeam(): void
    {
        // 1. Mocks
        $team = new Team();
        $teamRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $teamRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($team);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Team::class)
            ->willReturn($teamRepository);
            
        // Expect persist(User) to be called
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        // 2. Setup Employee
        $employee = new Employee();
        $employee->setFirstName('Jane');
        $employee->setLastName('Smith');
        $employee->setEmail('jane@example.com');
        // Team is null, User is null

        // 3. Run Listener
        $listener = new EmployeeEntityListener($hasher);
        $listener->prePersist($employee, $args);

        // 4. Verify Team Assignment
        $this->assertSame($team, $employee->getTeam());

        // 5. Verify User Sync
        $user = $employee->getUser();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('jane@example.com', $user->getEmail());
        $this->assertEquals('Jane Smith', $user->getName());
        // User::getRoles() usually includes ROLE_USER by default
        $this->assertContains('ROLE_EMP', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertSame($employee, $user->getEmployee());
    }

    public function testPrePersistSkipsUserCreationIfEmailMissing(): void
    {
        // Setup scenarios where partial logic runs (e.g. team assign) but User creation skipped
        $team = new Team();
        $teamRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $teamRepository->method('findOneBy')->willReturn($team);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($teamRepository);
        $entityManager->expects($this->never())->method('persist'); // No user persisted

        $args = $this->createMock(LifecycleEventArgs::class);
        $args->method('getObjectManager')->willReturn($entityManager);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $employee = new Employee();
        $employee->setFirstName('No');
        $employee->setLastName('Email');
        // No email set

        $listener = new EmployeeEntityListener($hasher);
        $listener->prePersist($employee, $args);

        $this->assertNull($employee->getUser());
    }
}
