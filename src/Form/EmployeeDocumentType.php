<?php

namespace App\Form;

use App\Entity\EmployeeDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'choices' => [
                    'Iqama' => EmployeeDocument::TYPE_IQAMA,
                    'National ID' => EmployeeDocument::TYPE_NATIONAL_ID,
                    'Passport' => EmployeeDocument::TYPE_PASSPORT,
                    'Driving License' => EmployeeDocument::TYPE_DRIVING_LICENSE,
                    'Other' => EmployeeDocument::TYPE_OTHER,
                ],
                'label' => 'Document Type',
            ])
            ->add('documentNumber', TextType::class, [
                'label' => 'Document Number',
                'required' => false,
            ])
            ->add('expiryDate', null, [
                'widget' => 'single_text',
                'label' => 'Expiry Date',
                'required' => true,
            ])
            ->add('file', FileType::class, [
                'label' => 'Upload Document',
                'mapped' => false,
                'required' => true,
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
            'data_class' => EmployeeDocument::class,
        ]);
    }
}
