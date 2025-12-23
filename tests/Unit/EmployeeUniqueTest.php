<?php

namespace App\Tests\Unit;

use App\Entity\Employee;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmployeeUniqueTest extends KernelTestCase
{
    private $entityManager;
    private $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->validator = static::getContainer()->get('validator');
    }

    public function testUniqueEmailConstraint(): void
    {
        // 1. Create and persist an employee
        $email = 'unique.' . uniqid() . '@test.com';
        $employee1 = new Employee();
        $employee1->setFirstName('Unit');
        $employee1->setLastName('One');
        $employee1->setEmail($email);
        $employee1->setBasicSalary('1000');
        // Set other required fields if needed for persistence, though validation might not need persistence if we just validate object
        // But UniqueEntity needs the first one in DB
        
        $this->entityManager->persist($employee1);
        $this->entityManager->flush();

        // 2. Create another employee with same email
        $employee2 = new Employee();
        $employee2->setFirstName('Unit');
        $employee2->setLastName('Two');
        $employee2->setEmail($email); // Duplicate

        // 3. Validate
        $errors = $this->validator->validate($employee2);

        // 4. Assert
        $this->assertGreaterThan(0, count($errors), 'Expected validation errors for duplicate email');
        
        $found = false;
        foreach ($errors as $error) {
            if ($error->getPropertyPath() === 'email' && str_contains($error->getMessage(), 'This email is already in use')) {
                $found = true;
                break;
            }
        }
        
        $this->assertTrue($found, 'Expected UniqueEntity violation message not found');
    }
}
