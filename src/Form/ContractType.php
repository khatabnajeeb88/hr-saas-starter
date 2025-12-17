<?php

namespace App\Form;

use App\Entity\Contract;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Saudi' => Contract::TYPE_SAUDI,
                    'Expatriate' => Contract::TYPE_EXPATRIATE,
                ],
                'label' => 'Contract Type',
            ])
            ->add('startDate', null, [
                'widget' => 'single_text',
                'label' => 'Start Date',
            ])
            ->add('endDate', null, [
                'widget' => 'single_text',
                'label' => 'End Date',
                'required' => false,
            ])
            ->add('basicSalary', MoneyType::class, [
                'currency' => 'SAR',
                'label' => 'Basic Salary',
            ])
            ->add('housingAllowance', MoneyType::class, [
                'currency' => 'SAR',
                'label' => 'Housing Allowance',
                'required' => false,
            ])
            ->add('transportAllowance', MoneyType::class, [
                'currency' => 'SAR',
                'label' => 'Transport Allowance',
                'required' => false,
            ])
            ->add('file', FileType::class, [
                'label' => 'Contract Document (PDF/Image)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF or image document',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
        ]);
    }
}
