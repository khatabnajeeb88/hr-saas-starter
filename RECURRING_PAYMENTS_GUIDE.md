# Recurring Payments & Email Notifications Setup Guide

## Overview

This guide explains how to set up and use the recurring payments and email notification system.

## Features Implemented

✅ **Recurring Payments**
- Automated subscription renewals
- Saved payment method charging
- Grace period handling (10 days)
- Retry logic (3 attempts: 0, 3, 7 days)
- Automatic suspension after failed retries

✅ **Email Notifications**
- Payment success receipts
- Payment failure alerts
- Trial ending reminders
- Renewal reminders
- Dunning notices
- Subscription suspended alerts

## Setup Instructions

### 1. Configure Email Service

Add to `.env.local`:

```env
# Mailer Configuration
MAILER_DSN=smtp://user:pass@smtp.example.com:587
# OR use SendGrid
MAILER_DSN=sendgrid://YOUR_API_KEY@default
# OR use Mailgun
MAILER_DSN=mailgun://KEY:DOMAIN@default
```

### 2. Run Database Migrations

```bash
# Generate migration for new fields
php bin/console make:migration

# Run migration
php bin/console doctrine:migrations:migrate
```

This adds to the `subscription` table:
- `next_billing_date` - When next payment is due
- `grace_period_ends_at` - Grace period expiration
- `retry_count` - Number of failed attempts

### 3. Set Up Cron Job

Add to crontab for automated renewals:

```bash
# Edit crontab
crontab -e

# Add this line (runs daily at 2 AM)
0 2 * * * cd /path/to/project && php bin/console app:payment:process-recurring >> /var/log/recurring-payments.log 2>&1
```

**For development/testing:**
```bash
# Run manually
php bin/console app:payment:process-recurring

# Dry run (no actual charges)
php bin/console app:payment:process-recurring --dry-run

# Process specific subscription
php bin/console app:payment:process-recurring --subscription-id=123
```

## How It Works

### Recurring Payment Flow

```
Daily Cron (2 AM)
  ↓
Find subscriptions where nextBillingDate <= today
  ↓
For each subscription:
  ↓
  Has saved payment method?
    ↓ Yes
    Charge payment method via Tap API
      ↓
      Success?
        ↓ Yes
        - Reset retry count
        - Update status to active
        - Extend billing period
        - Set next billing date
        - Send success email
        ↓ No
        - Increment retry count
        - Mark as past_due
        - Set grace period (10 days)
        - Schedule retry (0, 3, or 7 days)
        - Send failure/dunning email
        - If max retries (3) reached:
          - Suspend subscription
          - Send suspension email
```

### Email Notification Triggers

| Event | Email Template | Trigger |
|-------|---------------|---------|
| Payment Success | `payment_success.html.twig` | Webhook receives CAPTURED status |
| Payment Failure | `payment_failure.html.twig` | Webhook receives FAILED status |
| Trial Ending | `trial_ending.html.twig` | Manual trigger (3 days before) |
| Renewal Reminder | `renewal_reminder.html.twig` | Manual trigger (7 days before) |
| Dunning Notice | `dunning_notice.html.twig` | After failed payment retry |
| Subscription Suspended | `subscription_suspended.html.twig` | After 3 failed retries |

## Testing

### Test Recurring Payments

1. **Create test subscription with saved payment method:**
   ```bash
   # In your database
   UPDATE subscription 
   SET next_billing_date = CURRENT_DATE - INTERVAL '1 day',
       payment_method_id = 'card_test_token'
   WHERE id = 1;
   ```

2. **Run command:**
   ```bash
   php bin/console app:payment:process-recurring --dry-run
   ```

3. **Verify output:**
   - Should show subscription found
   - Should show payment method exists
   - Should show what would be charged

4. **Run actual charge:**
   ```bash
   php bin/console app:payment:process-recurring --subscription-id=1
   ```

### Test Email Delivery

1. **Configure test mailer:**
   ```env
   # Use Mailtrap for testing
   MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525
   ```

2. **Trigger payment:**
   - Complete a test payment
   - Check Mailtrap inbox
   - Verify email received

3. **Test all email types:**
   ```php
   // In a test controller or command
   $notificationService->sendPaymentSuccessEmail($payment);
   $notificationService->sendPaymentFailureEmail($payment);
   $notificationService->sendTrialEndingEmail($subscription);
   // etc.
   ```

### Test Dunning Process

1. **Use declined test card:**
   ```
   Card: 4000 0000 0000 0002
   ```

2. **Set subscription for renewal:**
   ```sql
   UPDATE subscription 
   SET next_billing_date = CURRENT_DATE,
       retry_count = 0
   WHERE id = 1;
   ```

3. **Run recurring payment command 3 times:**
   ```bash
   # Attempt 1 - fails, retry_count = 1
   php bin/console app:payment:process-recurring --subscription-id=1
   
   # Wait or manually update next_billing_date
   # Attempt 2 - fails, retry_count = 2
   php bin/console app:payment:process-recurring --subscription-id=1
   
   # Attempt 3 - fails, retry_count = 3, subscription suspended
   php bin/console app:payment:process-recurring --subscription-id=1
   ```

4. **Verify:**
   - Subscription status = canceled
   - Retry count = 3
   - Suspension email sent

## Customization

### Modify Retry Schedule

Edit `RecurringPaymentCommand.php`:

```php
private function getRetrySchedule(int $retryCount): ?int
{
    // Current: [0, 3, 7] days
    // Custom: [0, 1, 3, 7, 14] days
    $schedule = [0, 1, 3, 7, 14];
    
    return $schedule[$retryCount] ?? null;
}
```

### Change Grace Period

Edit `RecurringPaymentCommand.php`:

```php
private function handleFailedRenewal(Subscription $subscription): void
{
    // Change from 10 days to 14 days
    $gracePeriod = new \DateTimeImmutable('+14 days');
    $subscription->setGracePeriodEndsAt($gracePeriod);
    // ...
}
```

### Customize Email Templates

Email templates are in `templates/email/`:
- Modify HTML/CSS as needed
- Add your logo
- Change colors to match brand
- Add additional information

### Send Additional Emails

Add to `PaymentNotificationService.php`:

```php
public function sendCustomEmail(Subscription $subscription): void
{
    $team = $subscription->getTeam();
    $owner = $team->getOwner();

    $email = (new TemplatedEmail())
        ->from(new Address($this->fromEmail, $this->fromName))
        ->to(new Address($owner->getEmail()))
        ->subject('Your Custom Subject')
        ->htmlTemplate('email/custom_template.html.twig')
        ->context([
            'subscription' => $subscription,
            'team' => $team,
            'user' => $owner,
        ]);

    $this->mailer->send($email);
}
```

## Monitoring

### Check Cron Job Logs

```bash
# View recent runs
tail -f /var/log/recurring-payments.log

# Check for errors
grep "ERROR" /var/log/recurring-payments.log

# Count successful payments
grep "Payment successful" /var/log/recurring-payments.log | wc -l
```

### Monitor Failed Payments

```sql
-- Find subscriptions with failed retries
SELECT s.id, t.name, s.retry_count, s.grace_period_ends_at
FROM subscription s
JOIN team t ON s.team_id = t.id
WHERE s.status = 'past_due'
ORDER BY s.retry_count DESC;

-- Find subscriptions ending grace period soon
SELECT s.id, t.name, s.grace_period_ends_at
FROM subscription s
JOIN team t ON s.team_id = t.id
WHERE s.grace_period_ends_at <= CURRENT_DATE + INTERVAL '3 days'
AND s.status = 'past_due';
```

### Email Delivery Monitoring

Check application logs:

```bash
# View email logs
grep "email sent" var/log/dev.log

# Check for email failures
grep "Failed to send.*email" var/log/dev.log
```

## Troubleshooting

### Cron Job Not Running

1. Check crontab is set:
   ```bash
   crontab -l
   ```

2. Check cron service is running:
   ```bash
   service cron status
   ```

3. Check permissions:
   ```bash
   chmod +x bin/console
   ```

### Emails Not Sending

1. Verify mailer configuration:
   ```bash
   php bin/console debug:config framework mailer
   ```

2. Test mailer:
   ```bash
   php bin/console mailer:test [email protected]
   ```

3. Check logs:
   ```bash
   tail -f var/log/dev.log | grep mailer
   ```

### Payments Not Processing

1. Check Tap API credentials
2. Verify webhook is receiving events
3. Check payment method is valid
4. Review application logs

## Production Checklist

- [ ] Configure production mailer (SendGrid/Mailgun/SES)
- [ ] Set up cron job on production server
- [ ] Test cron job execution
- [ ] Verify email delivery
- [ ] Set up monitoring alerts
- [ ] Configure log rotation
- [ ] Test full payment cycle
- [ ] Document runbook for support team

## Next Steps

1. **Invoice Generation**: Create PDF invoices for payments
2. **Payment Analytics**: Build revenue dashboard
3. **Proration**: Calculate prorated amounts for plan changes
4. **Multi-Currency**: Support different currencies
5. **Advanced Dunning**: More sophisticated retry strategies
