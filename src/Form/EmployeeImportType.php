<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class EmployeeImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'CSV File',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotNull(
                        message: 'Please upload a file',
                    ),
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ],
                        mimeTypesMessage: 'Please upload a valid CSV or Excel file',
                    ),
                ],
                'attr' => [
                    'accept' => '.csv, .xlsx, .xls',
                    'class' => 'file-input file-input-bordered w-full',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
