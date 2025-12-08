<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('team'),
            AssociationField::new('plan'),
            ChoiceField::new('status')->setChoices([
                'Active' => 'active',
                'Canceled' => 'canceled',
                'Past Due' => 'past_due',
                'Trialing' => 'trialing',
            ]),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('currentPeriodEnd'),
            TextField::new('stripeSubscriptionId')->setLabel('Gateway Sub ID')->hideOnIndex(),
        ];
    }
}
