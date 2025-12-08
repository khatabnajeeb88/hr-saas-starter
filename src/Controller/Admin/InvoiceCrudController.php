<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class InvoiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Invoice::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('relatedTeam', 'Team'),
            MoneyField::new('amount')->setCurrency('USD'),
            ChoiceField::new('status')->setChoices([
                'Paid' => 'paid',
                'Open' => 'open',
                'Void' => 'void',
                'Uncollectible' => 'uncollectible',
            ]),
            TextField::new('number'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('paidAt'),
        ];
    }
}
