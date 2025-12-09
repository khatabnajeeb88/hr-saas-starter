<?php

namespace App\Controller\Admin;

use App\Entity\Announcement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AnnouncementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Announcement::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title'),
            ChoiceField::new('type')->setChoices([
                'Info' => 'info',
                'Success' => 'success',
                'Warning' => 'warning',
                'Danger' => 'danger',
            ]),
            TextEditorField::new('message'),
            BooleanField::new('isActive'),
            DateTimeField::new('startAt'),
            DateTimeField::new('endAt')->setRequired(false),
            AssociationField::new('readByUsers')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
        ];
    }
}
