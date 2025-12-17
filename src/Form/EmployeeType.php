<?php

namespace App\Form;

use App\Entity\Employee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'employee.form.labels.first_name',
                'attr' => ['placeholder' => 'employee.form.placeholders.first_name', 'class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'employee.form.labels.last_name',
                'attr' => ['placeholder' => 'employee.form.placeholders.last_name', 'class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'employee.form.labels.email',
                'required' => false,
                'attr' => ['placeholder' => 'employee.form.placeholders.email', 'class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('jobTitle', TextType::class, [
                'label' => 'employee.form.labels.job_title',
                'required' => false,
                'attr' => ['placeholder' => 'employee.form.placeholders.job_title', 'class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('nationalId', TextType::class, [
                'label' => 'employee.form.labels.national_id',
                'required' => false,
                'attr' => ['placeholder' => 'employee.form.placeholders.national_id', 'class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('nationalIdIssueDate', null, [
                'label' => 'employee.form.labels.issue_date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('nationalIdExpiryDate', null, [
                'label' => 'employee.form.labels.expiry_date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('department', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => \App\Entity\Department::class,
                'choice_label' => 'name',
                'label' => 'employee.form.labels.department',
                'required' => false,
                'placeholder' => 'employee.form.placeholders.select_department',
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('employmentStatus', ChoiceType::class, [
                'label' => 'employee.form.labels.employment_status',
                'placeholder' => 'employee.form.placeholders.select_status',
                'choices' => [
                    'employee.status.active' => 'active',
                    'employee.status.terminated' => 'terminated',
                    'employee.status.on_leave' => 'on_leave',
                ],
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            // Personal
            ->add('mobile', TextType::class, ['label' => 'employee.form.labels.mobile', 'required' => false, 'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']])
            ->add('gender', ChoiceType::class, [
                'label' => 'employee.form.labels.gender',
                'placeholder' => 'employee.form.placeholders.select_gender',
                'choices' => [
                    'employee.gender.male' => 'Male',
                    'employee.gender.female' => 'Female',
                ],
                'required' => false,
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('dateOfBirth', null, [
                'label' => 'employee.form.labels.dob',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('address', TextType::class, ['label' => 'employee.form.labels.address', 'required' => false, 'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']])
            ->add('city', TextType::class, ['label' => 'employee.form.labels.city', 'required' => false, 'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']])
            ->add('country', TextType::class, ['label' => 'employee.form.labels.country', 'required' => false, 'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']])

            // Bank
            ->add('bankName', TextType::class, ['label' => 'employee.form.labels.bank_name', 'required' => false, 'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']])
            ->add('bankIdentifierCode', TextType::class, ['label' => 'employee.form.labels.swift', 'required' => false, 'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']])
            ->add('bankBranch', TextType::class, ['label' => 'employee.form.labels.branch', 'required' => false, 'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']])
            ->add('bankAccountNumber', TextType::class, ['label' => 'employee.form.labels.account_number', 'required' => false, 'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']])

            // Work
             ->add('workType', ChoiceType::class, [
                'label' => 'employee.form.labels.work_type',
                'placeholder' => 'employee.form.placeholders.select_work_type',
                'choices' => [
                    'employee.work_type.office' => 'office',
                    'employee.work_type.remote' => 'remote',
                    'employee.work_type.hybrid' => 'hybrid',
                ],
                'required' => false,
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('shift', ChoiceType::class, [
                'label' => 'employee.form.labels.shift',
                'placeholder' => 'employee.form.placeholders.select_shift',
                'choices' => [
                    'employee.shift.regular' => 'regular',
                    'employee.shift.night' => 'night',
                ],
                'required' => false,
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
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
