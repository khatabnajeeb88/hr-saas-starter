<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PaymentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('subscription'),
            MoneyField::new('amount')->setCurrency('USD'),
            TextField::new('currency'),
            ChoiceField::new('status')->setChoices([
                'Pending' => 'pending',
                'Success' => 'succeeded',
                'Failed' => 'failed',
            ]),
            TextField::new('tapChargeId')->setLabel('Charge ID'),
            DateTimeField::new('createdAt'),
            CodeEditorField::new('gatewayResponse')->hideOnIndex()->setLanguage('js'),
        ];
    }
}
