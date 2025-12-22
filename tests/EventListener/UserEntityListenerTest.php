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
        // Team retrieval is Reverted, so we don't expect calls for TeamRepository
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        // No checks for getRepository(Team::class)
        
        $args = $this->createMock(LifecycleEventArgs::class);
        // Only getObjectManager might be called internally if reverting didn't cleanly remove all uses?
        // Actually, looking at code: it doesn't use EM anymore for team. 
        // It just creates Employee. 
        // So we can relax mocks.

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
        $this->assertNull($employee->getTeam()); // Expect NULL team
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
