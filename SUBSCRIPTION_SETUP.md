# Subscription Management Setup Guide

## Database Setup

### 1. Generate Migration

Once your database is configured and running, generate the migration for the subscription tables:

```bash
php bin/console make:migration
```

### 2. Run Migration

Apply the migration to create the subscription tables:

```bash
php bin/console doctrine:migrations:migrate
```

This will create the following tables:
- `subscription_plan` - Stores subscription plan information
- `subscription` - Stores team subscriptions
- `subscription_feature` - Stores available features
- `plan_feature` - Links features to plans with values

### 3. Seed Default Data

Populate the database with default plans and features:

```bash
php bin/console app:subscription:seed
```

This will create:

**Plans:**
- **Free** ($0/month) - 3 team members, 1GB storage
- **Starter** ($19/month) - 10 team members, 10GB storage, API access
- **Pro** ($49/month) - 50 team members, 100GB storage, custom branding, analytics
- **Enterprise** ($199/month) - Unlimited members, unlimited storage, all features

**Features:**
- API Access
- Storage Limit
- Custom Branding
- Priority Support
- Advanced Analytics
- Team Collaboration

## Usage

### Accessing Subscription Pages

- **View Plans**: `/subscription/plans`
- **Current Subscription**: `/subscription/current`
- **Subscribe to Plan**: `/subscription/subscribe/{planSlug}`
- **Upgrade/Downgrade**: `/subscription/upgrade`
- **Cancel Subscription**: `/subscription/cancel`
- **Resume Subscription**: `/subscription/resume`

### Programmatic Usage

#### Check if Team Has Feature

```php
use App\Service\SubscriptionManager;

public function __construct(
    private SubscriptionManager $subscriptionManager
) {}

public function someAction(Team $team): void
{
    if ($this->subscriptionManager->hasFeature($team, 'api-access')) {
        // Team has API access
    }
    
    $storageLimit = $this->subscriptionManager->getFeatureValue($team, 'storage-limit');
}
```

#### Create Subscription

```php
use App\Service\SubscriptionManager;
use App\Repository\SubscriptionPlanRepository;

public function subscribe(
    Team $team,
    SubscriptionManager $subscriptionManager,
    SubscriptionPlanRepository $planRepository
): void {
    $plan = $planRepository->findBySlug('pro');
    
    // Create with 14-day trial
    $subscription = $subscriptionManager->createSubscription($team, $plan, trial: true, trialDays: 14);
}
```

#### Change Plan

```php
$newPlan = $planRepository->findBySlug('enterprise');
$subscriptionManager->changePlan($subscription, $newPlan);
```

#### Cancel Subscription

```php
// Cancel at end of period
$subscriptionManager->cancelSubscription($subscription, immediately: false);

// Cancel immediately
$subscriptionManager->cancelSubscription($subscription, immediately: true);
```

### Managing Plans (Admin)

#### Create New Plan

```php
use App\Service\PlanManager;

$planManager->createPlan([
    'name' => 'Custom Plan',
    'slug' => 'custom',
    'description' => 'A custom plan',
    'price' => '99.00',
    'billing_interval' => 'monthly',
    'team_member_limit' => 25,
    'is_active' => true,
    'display_order' => 5,
]);
```

#### Add Feature to Plan

```php
$plan = $planRepository->findBySlug('pro');
$feature = $featureRepository->findBySlug('api-access');

$planManager->addFeatureToPlan($plan, $feature, value: 'true', enabled: true);
```

## Security

### Voter Permissions

The `SubscriptionVoter` provides the following permissions:

- `SUBSCRIPTION_VIEW` - Any team member can view
- `SUBSCRIPTION_MANAGE` - Owner and admins can manage
- `SUBSCRIPTION_CANCEL` - Only owner can cancel
- `SUBSCRIPTION_UPGRADE` - Owner and admins can upgrade/downgrade

### Usage in Controllers

```php
$this->denyAccessUnlessGranted('SUBSCRIPTION_MANAGE', $subscription);
```

### Usage in Templates

```twig
{% if is_granted('SUBSCRIPTION_CANCEL', subscription) %}
    <a href="{{ path('subscription_cancel') }}">Cancel Subscription</a>
{% endif %}
```

## Scheduled Tasks

### Process Expired Trials

Create a cron job or scheduled task to process expired trials:

```php
use App\Service\SubscriptionManager;

// In a command or scheduled task
$count = $subscriptionManager->processExpiredTrials();
// Returns number of trials that were expired
```

## Next Steps

1. **Payment Integration**: Integrate with Stripe, PayPal, or your preferred payment gateway
2. **Webhooks**: Set up webhooks to handle payment events
3. **Invoicing**: Add invoice generation and billing history
4. **Email Notifications**: Send emails for subscription events (trial ending, payment failed, etc.)
5. **Usage Tracking**: Track feature usage against plan limits
6. **Admin Dashboard**: Create admin interface for managing all subscriptions
