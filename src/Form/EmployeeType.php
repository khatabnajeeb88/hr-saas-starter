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
                'attr' => ['placeholder' => 'John']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'employee.form.labels.last_name',
                'attr' => ['placeholder' => 'Doe']
            ])
            ->add('email', EmailType::class, [
                'label' => 'employee.form.labels.email',
                'required' => false,
                'attr' => ['placeholder' => 'john.doe@company.com']
            ])
            ->add('jobTitle', TextType::class, [
                'label' => 'employee.form.labels.job_title',
                'required' => false,
                'attr' => ['placeholder' => 'Software Engineer']
            ])
            ->add('department', TextType::class, [
                'label' => 'employee.form.labels.department',
                'required' => false,
                'attr' => ['placeholder' => 'Engineering']
            ])
            ->add('employmentStatus', ChoiceType::class, [
                'label' => 'employee.form.labels.employment_status',
                'choices' => [
                    'employee.status.active' => 'active',
                    'employee.status.terminated' => 'terminated',
                    'employee.status.on_leave' => 'on_leave',
                ],
            ])
            // Personal
            ->add('mobile', TextType::class, ['label' => 'employee.form.labels.mobile', 'required' => false])
            ->add('gender', ChoiceType::class, [
                'label' => 'employee.form.labels.gender',
                'choices' => [
                    'employee.gender.male' => 'Male',
                    'employee.gender.female' => 'Female',
                    'employee.gender.other' => 'Other',
                ],
                'required' => false,
            ])
            ->add('dateOfBirth', null, [
                'label' => 'employee.form.labels.dob',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('address', TextType::class, ['label' => 'employee.form.labels.address', 'required' => false])
            ->add('city', TextType::class, ['label' => 'employee.form.labels.city', 'required' => false])
            ->add('country', TextType::class, ['label' => 'employee.form.labels.country', 'required' => false])

            // Bank
            ->add('bankName', TextType::class, ['label' => 'employee.form.labels.bank_name', 'required' => false])
            ->add('bankIdentifierCode', TextType::class, ['label' => 'employee.form.labels.swift', 'required' => false])
            ->add('bankBranch', TextType::class, ['label' => 'employee.form.labels.branch', 'required' => false])
            ->add('bankAccountNumber', TextType::class, ['label' => 'employee.form.labels.account_number', 'required' => false])

            // Work
             ->add('workType', ChoiceType::class, [
                'label' => 'employee.form.labels.work_type',
                'choices' => [
                    'employee.work_type.office' => 'office',
                    'employee.work_type.remote' => 'remote',
                    'employee.work_type.hybrid' => 'hybrid',
                ],
                'required' => false,
            ])
            ->add('shift', ChoiceType::class, [
                'label' => 'employee.form.labels.shift',
                'choices' => [
                    'employee.shift.regular' => 'regular',
                    'employee.shift.night' => 'night',
                ],
                'required' => false,
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
