<?php

namespace App\Controller\Admin;

use App\Entity\TeamInvitation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TeamInvitationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TeamInvitation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email'),
            TextField::new('token')->onlyOnDetail(),
            AssociationField::new('team'),
        ];
    }
}
