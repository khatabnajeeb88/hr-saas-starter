<?php

namespace App\Form;

use App\Entity\Employee;
use App\Entity\EmployeeTag;
use App\Entity\FamilyMember;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EmployeePortalProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Personal Details
            ->add('firstName', TextType::class, [
                'disabled' => true, // Read-only
                'label' => 'First Name'
            ])
            ->add('lastName', TextType::class, [
                 'disabled' => true, // Read-only
                 'label' => 'Last Name'
            ])
            ->add('email', EmailType::class, [
                'label' => 'Personal Email',
                'required' => false,
            ])
            ->add('mobile', TelType::class, [
                'label' => 'Mobile Number'
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Male' => 'Male',
                    'Female' => 'Female',
                ],
                'placeholder' => 'Select Gender',
            ])
            ->add('dateOfBirth', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('maritalStatus', ChoiceType::class, [
                'choices' => [
                    'Single' => 'Single',
                    'Married' => 'Married',
                    'Divorced' => 'Divorced',
                    'Widowed' => 'Widowed',
                ],
                'placeholder' => 'Select Status',
                'required' => false,
            ])
            
            // Address
            ->add('address', TextareaType::class, [
                'attr' => ['rows' => 3],
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'required' => false,
            ])
            ->add('country', CountryType::class, [
                'placeholder' => 'Select Country',
                'required' => false,
            ])

             // Bank Details
            ->add('bankName', TextType::class, [
                'required' => false,
                'label' => 'Bank Name'
            ])
            ->add('bankAccountNumber', TextType::class, [
                'required' => false,
                'label' => 'Account Number'
            ])
            ->add('iban', TextType::class, [
                'required' => false,
                'label' => 'IBAN'
            ])

            // Family Members (Dependents)
            // Using CollectionType to allow adding/removing family members
             ->add('familyMembers', CollectionType::class, [
                'entry_type' => FamilyMemberType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
        ]);
    }
}
