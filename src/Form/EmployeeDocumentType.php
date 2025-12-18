<?php

namespace App\Form;

use App\Entity\EmployeeDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EmployeeDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'employee.document.type',
                'choices' => [
                    'employee.document.types.iqama' => EmployeeDocument::TYPE_IQAMA,
                    'employee.document.types.national_id' => EmployeeDocument::TYPE_NATIONAL_ID,
                    'employee.document.types.passport' => EmployeeDocument::TYPE_PASSPORT,
                    'employee.document.types.driving_license' => EmployeeDocument::TYPE_DRIVING_LICENSE,
                    'employee.document.types.other' => EmployeeDocument::TYPE_OTHER,
                ],
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('documentNumber', TextType::class, [
                'label' => 'employee.document.number',
                'required' => false,
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('expiryDate', DateType::class, [
                'label' => 'employee.document.expiry',
                'widget' => 'single_text',
                'required' => false,
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'shadow-none py-2.5 px-4 border-1 border-gray-300 focus:ring-purple-500 focus:border-purple-500 block w-full sm:text-sm rounded-md']
            ])
            ->add('file', FileType::class, [
                'label' => 'employee.document.file',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        mimeTypesMessage: 'Please upload a valid PDF or Image document'
                    )
                ],
                'attr' => ['class' => 'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100', 'accept' => '.pdf,.jpg,.jpeg,.png']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployeeDocument::class,
        ]);
    }
}
