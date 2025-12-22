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
    public function testPrePersistSyncsUser(): void
    {
        // 1. Mocks
        // Team repository should NOT be called anymore
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        // Expect persist(User) to be called
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects($this->any()) // getObjectManager called for user sync or contract?
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
        
        // 3. Run Listener
        $listener = new EmployeeEntityListener($hasher);
        $listener->prePersist($employee, $args);

        // 4. Verify Team is still NULL (No auto-assignment)
        $this->assertNull($employee->getTeam());

        // 4.1 Verify Draft Contract Created
        $this->assertFalse($employee->getContracts()->isEmpty());
        $this->assertEquals('draft', $employee->getContracts()->first()->getStatus());

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
        // Setup scenarios where partial logic runs but User creation skipped
        $entityManager = $this->createMock(EntityManagerInterface::class);
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
