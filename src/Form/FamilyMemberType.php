<?php

namespace App\Form;

use App\Entity\FamilyMember;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FamilyMemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'family_member.form.name',
                'attr' => ['class' => 'input input-bordered w-full bg-slate-50 dark:bg-slate-900 dark:text-slate-100 mb-2']
            ])
            ->add('relationship', TextType::class, [
                'label' => 'family_member.form.relationship',
                'attr' => ['class' => 'input input-bordered w-full bg-slate-50 dark:bg-slate-900 dark:text-slate-100 mb-2']
            ])
            ->add('dateOfBirth', DateType::class, [
                'label' => 'family_member.form.date_of_birth',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
                'attr' => ['class' => 'input input-bordered w-full bg-slate-50 dark:bg-slate-900 dark:text-slate-100 mb-2']
            ])
            ->add('phone', TextType::class, [
                'label' => 'family_member.form.phone',
                'required' => false,
                'attr' => ['class' => 'input input-bordered w-full bg-slate-50 dark:bg-slate-900 dark:text-slate-100']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FamilyMember::class,
        ]);
    }
}
