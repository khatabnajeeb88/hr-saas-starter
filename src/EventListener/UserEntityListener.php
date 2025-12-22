<?php

namespace App\EventListener;

use App\Entity\Employee;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
class UserEntityListener
{
    public function __construct(
        private \Symfony\Component\String\Slugger\SluggerInterface $slugger
    ) {
    }

    public function prePersist(User $user, LifecycleEventArgs $event): void
    {
        if ($user->getEmployee() !== null) {
            return;
        }

        $employee = new Employee();
        
        $name = $user->getName();
        $firstName = 'New';
        $lastName = 'User';

        if ($name) {
            $parts = explode(' ', trim($name), 2);
            $firstName = $parts[0];
            if (isset($parts[1]) && trim($parts[1]) !== '') {
                $lastName = $parts[1];
            } else {
                $lastName = $firstName; // Fallback
            }
        } elseif ($user->getEmail()) {
             $emailParts = explode('@', $user->getEmail());
             if (isset($emailParts[0])) {
                 $firstName = ucfirst($emailParts[0]);
             }
        }

        $employee->setFirstName($firstName);
        $employee->setLastName($lastName);
        $employee->setEmail($user->getEmail());
        $employee->setWorkEmail($user->getEmail()); // Optional: maybe leave null? But good for now.
        
        // --- Team Creation Logic ---
        // Reverted: We leave Team as NULL to allow Onboarding flow to handle it.
        // ---------------------------

        // Important: Set the relationship
        $employee->setUser($user);
        $user->setEmployee($employee);
    }
}
