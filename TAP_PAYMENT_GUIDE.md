# Tap Payment Gateway Integration Guide

## Overview

This guide explains how to set up and use the Tap Payment Gateway integration for processing subscription payments.

## Prerequisites

1. **Tap Merchant Account**: Sign up at https://dashboard.tap.company
2. **API Keys**: Obtain test and live API keys from your Tap dashboard
3. **Webhook URL**: A publicly accessible URL (use ngrok for local development)

## Setup Instructions

### 1. Configure Environment Variables

Add the following to your `.env.local` file:

```env
# Tap Payment Gateway
TAP_SECRET_KEY=sk_test_YOUR_TEST_KEY
TAP_PUBLIC_KEY=pk_test_YOUR_PUBLIC_KEY
TAP_API_URL=https://api.tap.company/v2
```

**Important**: Replace the test keys with your actual keys from the Tap dashboard.

### 2. Run Database Migrations

```bash
# Generate migration for payment table
php bin/console make:migration

# Run migration
php bin/console doctrine:migrations:migrate
```

### 3. Configure Webhook URL

In your Tap dashboard:
1. Go to Settings → Webhooks
2. Add webhook URL: `https://yourdomain.com/webhook/tap`
3. For local development, use ngrok:
   ```bash
   ngrok http 8000
   # Use the ngrok URL: https://abc123.ngrok.io/webhook/tap
   ```

## Payment Flow

### 1. User Subscribes to a Plan

```
User → Select Plan → Subscribe
  ↓
Create Subscription (status: pending_payment)
  ↓
Redirect to Tap Checkout
```

### 2. Payment Processing

```
Tap Checkout → User Enters Card Details
  ↓
Tap Processes Payment
  ↓
Success → Redirect to /payment/success
  ↓
Webhook Received at /webhook/tap
  ↓
Subscription Activated (status: active)
```

### 3. Webhook Handling

The webhook controller:
- Validates signature using HMAC-SHA256
- Creates Payment record
- Updates Subscription status
- Saves payment method (if requested)

## Testing

### Test Cards

Use these test cards provided by Tap:

| Card Number | Type | Result |
|------------|------|--------|
| 4111 1111 1111 1111 | Visa | Success |
| 4000 0000 0000 0002 | Visa | Declined |
| 4000 0000 0000 3220 | Visa | 3D Secure Required |

### Test Flow

1. **Subscribe to a Paid Plan**:
   ```
   Visit: /subscription/plans
   Select: Pro Plan ($49/month)
   Click: Choose Pro
   ```

2. **Complete Payment**:
   - Enter test card: 4111 1111 1111 1111
   - Expiry: Any future date
   - CVV: Any 3 digits
   - Click Pay

3. **Verify Success**:
   - Should redirect to `/payment/success`
   - Check subscription status: `active`
   - Verify payment record created

4. **Check Webhook**:
   - View logs: `tail -f var/log/dev.log`
   - Look for: "Tap webhook received"
   - Verify signature validation passed

## API Reference

### TapPaymentService Methods

```php
// Create a charge
$chargeData = $tapPaymentService->createCharge(
    $subscription,
    $redirectUrl,
    $webhookUrl,
    $savedCardToken // optional
);

// Retrieve charge details
$charge = $tapPaymentService->retrieveCharge($chargeId);

// Process refund
$refund = $tapPaymentService->refundCharge(
    $chargeId,
    $amount,
    $currency,
    $reason
);

// Validate webhook
$isValid = $tapPaymentService->validateWebhookSignature(
    $payload,
    $hashString
);

// Charge saved payment method
$chargeData = $tapPaymentService->chargePaymentMethod(
    $subscription,
    $webhookUrl
);
```

### Routes

| Route | Method | Description |
|-------|--------|-------------|
| `/payment/checkout/{id}` | GET | Initiate payment for subscription |
| `/payment/success` | GET | Payment success callback |
| `/payment/failure` | GET | Payment failure callback |
| `/payment/methods` | GET | Manage saved payment methods |
| `/webhook/tap` | POST | Tap webhook endpoint |

## Webhook Payload Example

```json
{
  "id": "chg_TS05A4120230736x9K22710693",
  "object": "charge",
  "status": "CAPTURED",
  "amount": 49.00,
  "currency": "SAR",
  "customer": {
    "id": "cus_TS07A5420232136o2K52709053",
    "email": "[email protected]"
  },
  "card": {
    "id": "card_IIGi4523416sFHe27jJ9E589",
    "last_four": "1111",
    "brand": "VISA"
  },
  "metadata": {
    "subscription_id": "123",
    "team_id": "456"
  }
}
```

## Security

### Webhook Signature Validation

The webhook signature is validated using HMAC-SHA256:

```php
$hashString = 'x_id' . $id .
              'x_amount' . $amount .
              'x_currency' . $currency .
              'x_gateway_reference' . $gatewayRef .
              'x_payment_reference' . $paymentRef .
              'x_status' . $status .
              'x_created' . $created;

$computed = hash_hmac('sha256', $hashString, $secretKey);

if ($computed === $receivedHashString) {
    // Webhook is authentic
}
```

### Best Practices

1. **Never commit API keys** - Use environment variables
2. **Always validate webhooks** - Check signature before processing
3. **Use HTTPS** - Required for production webhooks
4. **Handle idempotency** - Webhooks may be delivered multiple times
5. **Log everything** - Keep detailed logs for debugging

## Troubleshooting

### Payment Not Processing

1. Check API keys are correct
2. Verify webhook URL is accessible
3. Check logs for errors: `tail -f var/log/dev.log`
4. Test with Tap's test cards

### Webhook Not Received

1. Verify webhook URL in Tap dashboard
2. Check firewall/security settings
3. Use ngrok for local testing
4. Check webhook logs in Tap dashboard

### Signature Validation Failing

1. Ensure secret key is correct
2. Check amount formatting (2 decimal places)
3. Verify all required fields are present
4. Check for extra whitespace in values

## Going Live

### Pre-Launch Checklist

- [ ] Replace test API keys with live keys
- [ ] Update webhook URL to production domain
- [ ] Test with real cards (small amounts)
- [ ] Set up monitoring and alerts
- [ ] Configure email notifications
- [ ] Test refund process
- [ ] Review error handling
- [ ] Set up backup webhook URL

### Production Configuration

```env
# Production .env
TAP_SECRET_KEY=sk_live_YOUR_LIVE_SECRET_KEY
TAP_PUBLIC_KEY=pk_live_YOUR_LIVE_PUBLIC_KEY
TAP_API_URL=https://api.tap.company/v2
```

## Support

- **Tap Documentation**: https://developers.tap.company
- **Tap Support**: [email protected]
- **Dashboard**: https://dashboard.tap.company

## Next Steps

1. **Email Notifications**: Send payment receipts and failure alerts
2. **Invoice Generation**: Create PDF invoices for payments
3. **Recurring Payments**: Automatically charge saved cards
4. **Dunning Management**: Retry failed payments
5. **Multi-Currency**: Support different currencies
6. **Apple Pay / Google Pay**: Add alternative payment methods
