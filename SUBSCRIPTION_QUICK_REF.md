# Subscription Management Quick Reference

## 🚀 Quick Start

```bash
# 1. Run migrations (when database is configured)
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 2. Seed default plans and features
php bin/console app:subscription:seed

# 3. Access subscription pages
# - Plans: /subscription/plans
# - Current: /subscription/current
```

## 📦 What Was Created

- **4 Entities**: SubscriptionPlan, Subscription, SubscriptionFeature, PlanFeature
- **4 Repositories**: Query methods for plans, subscriptions, features
- **2 Services**: SubscriptionManager, PlanManager
- **1 Voter**: SubscriptionVoter (access control)
- **1 Controller**: SubscriptionController (6 routes)
- **5 Templates**: plans, current, subscribe, upgrade, cancel
- **1 Command**: app:subscription:seed

## 💡 Common Usage

### Check Feature Access
```php
if ($subscriptionManager->hasFeature($team, 'api-access')) {
    // Team has API access
}
```

### Create Subscription
```php
$plan = $planRepository->findBySlug('pro');
$subscription = $subscriptionManager->createSubscription($team, $plan, trial: true);
```

### Change Plan
```php
$newPlan = $planRepository->findBySlug('enterprise');
$subscriptionManager->changePlan($subscription, $newPlan);
```

### Cancel Subscription
```php
// Cancel at period end
$subscriptionManager->cancelSubscription($subscription, immediately: false);
```

## 🎯 Default Plans

| Plan | Price | Members | Storage | Features |
|------|-------|---------|---------|----------|
| Free | $0/mo | 3 | 1GB | Basic |
| Starter | $19/mo | 10 | 10GB | + API |
| Pro | $49/mo | 50 | 100GB | + Branding, Analytics |
| Enterprise | $199/mo | Unlimited | Unlimited | All Features |

## 🔒 Permissions

- `SUBSCRIPTION_VIEW` - Any team member
- `SUBSCRIPTION_MANAGE` - Owner & admins
- `SUBSCRIPTION_CANCEL` - Owner only
- `SUBSCRIPTION_UPGRADE` - Owner & admins

## 📍 Routes

- `/subscription/plans` - View all plans
- `/subscription/current` - Current subscription
- `/subscription/subscribe/{slug}` - Subscribe to plan
- `/subscription/upgrade` - Change plan
- `/subscription/cancel` - Cancel subscription
- `/subscription/resume` - Resume subscription

## ⚠️ Not Included

- Payment gateway integration (Stripe, PayPal)
- Webhooks for payment events
- Invoice generation
- Email notifications
- Usage tracking enforcement
- Admin dashboard

## 📚 Documentation

See [SUBSCRIPTION_SETUP.md](file:///Users/khatabmustafa/Sites/saas-starter-pack/SUBSCRIPTION_SETUP.md) for detailed setup and usage instructions.
