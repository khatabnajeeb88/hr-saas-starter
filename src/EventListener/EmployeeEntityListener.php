<?php

namespace App\EventListener;

use App\Entity\Employee;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Employee::class)]
class EmployeeEntityListener
{
    public function __construct(
        private \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function prePersist(Employee $employee, LifecycleEventArgs $event): void
    {
        $em = $event->getObjectManager();

        // 1. Team Assignment:
        // We do NOT assign a default team anymore. 
        // We let the Onboarding flow or the Admin Form handle it.
        // if ($employee->getTeam() === null) { ... }

        // 2. Sync: Create User if not exists
        if ($employee->getUser() !== null) {
            return;
        }

        // Need an email to create a User
        $email = $employee->getWorkEmail() ?? $employee->getEmail();
        if (empty($email)) {
            // Cannot create user without email. 
            // We could throw exception or skip. Skipping allows flexible creation if email missing.
            return; 
        }

        $user = new \App\Entity\User();
        $user->setEmail($email);
        $user->setName($employee->getFullName());
        $user->setRoles(['ROLE_EMP']);

        // Generate a random password (or set a default known one for initial setup?)
        // Let's use a secure random string.
        $plainPassword = bin2hex(random_bytes(8)); 
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Bi-directional link
        $user->setEmployee($employee);
        $employee->setUser($user);

        // Persist the new User
        // Since we are in prePersist of Employee, persisting User is fine.
        // The UserEntityListener will run for this User.
        // It checks `$user->getEmployee()`. We just set it. So it should return early.
        $em->persist($user);
        
        // Note: The User will be flushed along with the Employee if this is part of the current UoW.
        // However, we are in prePersist. Doctrine usually handles new entities added to UoW here.
        // Re-computing change set might be needed if we modify the *Employee* significantly, 
        // but here we modified Employee ($user property) and added a new entity. 
        // 3. create a draft contract if none exists
        if ($employee->getContracts()->isEmpty()) {
            $contract = new \App\Entity\Contract();
            $contract->setStatus(\App\Entity\Contract::STATUS_DRAFT);
            // Required fields defaults
            $contract->setType(\App\Entity\Contract::TYPE_SAUDI); 
            $contract->setStartDate(new \DateTimeImmutable());
            $contract->setBasicSalary('0.00');
            
            $employee->addContract($contract);
            // Cascade persist on Employee::$contracts will handle persistence
        }
    }
}
