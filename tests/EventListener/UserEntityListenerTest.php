<?php

namespace App\Tests\EventListener;

use App\Entity\Employee;
use App\Entity\Team;
use App\Entity\User;
use App\EventListener\UserEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

class UserEntityListenerTest extends TestCase
{
    public function testPrePersistCreatesEmployeeAndAssignsTeam(): void
    {
        // 1. Mock EntityManager and Repository
        $team = new Team();
        // Since Team doesn't have a specific ID setter for mocking without reflection, 
        // we just rely on the object being returned. 
        // Logic checks if ($team) { $employee->setTeam($team) };
        
        $teamRepository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $teamRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($team);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Team::class)
            ->willReturn($teamRepository);

        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        // 2. Setup User
        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john@example.com');

        // 3. Run Listener
        // Mock Slugger
        $slugger = $this->createMock(\Symfony\Component\String\Slugger\SluggerInterface::class);
        $listener = new UserEntityListener($slugger);
        $listener->prePersist($user, $args);

        // 4. Verify assertions
        $employee = $user->getEmployee();
        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals('John', $employee->getFirstName());
        $this->assertEquals('Doe', $employee->getLastName());
        $this->assertEquals('john@example.com', $employee->getEmail());
        $this->assertSame($team, $employee->getTeam());
        $this->assertSame($user, $employee->getUser());
    }

    public function testPrePersistSkipsIfEmployeeExists(): void
    {
        $user = new User();
        $employee = new Employee();
        $user->setEmployee($employee);

        // Expect no interaction with EntityManager
        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects($this->never())->method('getObjectManager');

        // Mock Slugger
        $slugger = $this->createMock(\Symfony\Component\String\Slugger\SluggerInterface::class);
        $listener = new UserEntityListener($slugger);
        $listener->prePersist($user, $args);
    }
}
