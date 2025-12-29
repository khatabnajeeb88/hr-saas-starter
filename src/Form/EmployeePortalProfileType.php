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
            ->add('profileImage', FileType::class, [
                'label' => 'portal.profile.edit.profile_image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'portal.profile.edit.image_mime_error',
                    )
                ],
                'attr' => ['class' => 'file-input file-input-bordered w-full bg-slate-50 dark:bg-slate-900 dark:text-slate-100'],
            ])
            ->add('firstName', TextType::class, [
                'disabled' => true, // Read-only
                'label' => 'portal.profile.first_name'
            ])
            ->add('lastName', TextType::class, [
                 'disabled' => true, // Read-only
                 'label' => 'portal.profile.last_name'
            ])
            ->add('email', EmailType::class, [
                'label' => 'portal.profile.email',
                'required' => false,
            ])
            ->add('mobile', TelType::class, [
                'label' => 'portal.profile.phone'
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'employee.gender.male' => 'Male',
                    'employee.gender.female' => 'Female',
                ],
                // 'choice_translation_domain' => 'messages', // Default is true, so keys above will be translated
                'placeholder' => 'portal.profile.edit.select_gender',
                'label' => 'portal.profile.gender'
            ])
            ->add('dateOfBirth', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'portal.profile.dob'
            ])
            ->add('maritalStatus', ChoiceType::class, [
                'choices' => [
                    'employee.marital_status.single' => 'Single',
                    'employee.marital_status.married' => 'Married',
                    'employee.marital_status.divorced' => 'Divorced',
                    'employee.marital_status.widowed' => 'Widowed',
                ],
                'placeholder' => 'portal.profile.edit.select_marital_status',
                'required' => false,
                'label' => 'portal.profile.marital_status'
            ])
            
            // Address
            ->add('address', TextareaType::class, [
                'attr' => ['rows' => 3],
                'required' => false,
                'label' => 'portal.profile.address'
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'label' => 'portal.profile.city'
            ])
            ->add('country', CountryType::class, [
                'placeholder' => 'portal.profile.edit.select_country',
                'required' => false,
                'label' => 'portal.profile.country'
            ])

             // Bank Details
            ->add('bankName', TextType::class, [
                'required' => false,
                'label' => 'portal.profile.bank_name'
            ])
            ->add('bankAccountNumber', TextType::class, [
                'required' => false,
                'label' => 'portal.profile.account_number'
            ])
            ->add('iban', TextType::class, [
                'required' => false,
                'label' => 'portal.profile.iban'
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
