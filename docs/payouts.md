# Payouts

Purpose: send money out of your wallet to a phone or account. The wallet is debited
before the provider is called; if a payout is confirmed failed, debit and fees are
returned together.

Prerequisites: a valid token, enough balance, an allowed country and provider.
Business payouts need the `BUSINESS_PAYOUT` API key scope.

All creates take an optional `Idempotency-Key` ([idempotency.md](idempotency.md)).
The three `payouts` methods also take an optional `merchantAccount`
([rails.md](rails.md)). Payouts never accept the merchant `fees` field.

## Request (same body for every payout path)

| Field | Required | Notes |
| --- | --- | --- |
| `provider_code` | yes | |
| `reference` | yes | your `merchant_reference`, unique per application |
| `amount` | yes | decimal string |
| `currency` | yes | |
| `destination_account` | yes | phone/account; for merchant-account payouts the provider shortcode |
| `destination_name` | no | |
| `purpose`, `purpose_code` | no | mandatory for business payouts (contract: "Purpose is mandatory") |
| `callback_url` | no | |
| `fee_bearer` | no | `MERCHANT` or `CUSTOMER` |
| `application_fee`, `application_fee_percent` | no | platforms; not together |
| `metadata` | no | |

## Standard payout

`POST /transactions/payouts/` (HTTP 201).

```php
$payout = $client->payouts->create([
    'provider_code' => 'VODACOM_MPESA_COD',
    'reference' => 'PAYOUT-77',
    'amount' => '50.00',
    'currency' => 'USD',
    'destination_account' => '243820000000',
    'destination_name' => 'Jane Doe',
], idempotencyKey: 'payout-77');
```

## Business payouts

```php
// POST /transactions/payouts/business/ : to a beneficiary phone number (B2C)
$client->payouts->createBusiness($body + ['purpose' => 'Supplier payment'], 'payout-78');

// POST /transactions/payouts/business/merchant-account/ : to a provider merchant account (shortcode, B2B)
$client->payouts->createBusinessMerchantAccount($body + ['purpose' => 'Settlement'], 'payout-79');

// POST /transactions/card-payouts/business/merchant-account/ : card-payout variant (same body)
$client->cardPayouts->createBusinessMerchantAccount($body + ['purpose' => 'Settlement'], 'payout-80');
```

For merchant-account payouts `destination_account` must be the shortcode, not a phone
number. The card-payout path lists no `X-Merchant-Account` in the contract.

## Response

```json
{
  "status": "PENDING", "transaction_id": "...", "internal_reference": "...",
  "external_reference": "...", "merchant_reference": "PAYOUT-77", "timestamp": "...",
  "provider_reference": null, "message": "...", "error_code": null, "error_message": null,
  "data": {}, "processing_time_ms": 380
}
```

A provider failure is reported as `PENDING` (HTTP 202 per the contract's fee notes),
never as final: confirm through [webhooks](webhooks.md) or [status](status.md).
Failures before the provider call (validation, limits, insufficient balance) are
final `FAILED`/400.

## Errors

400 (validation, insufficient balance, limits), 403 (scope, seller), 409
(idempotency), 401. See [errors.md](errors.md).

## Minimal example

```php
$payout = $client->payouts->create([
    'provider_code' => 'VODACOM_MPESA_COD', 'reference' => 'PAYOUT-77',
    'amount' => '50.00', 'currency' => 'USD', 'destination_account' => '243820000000',
], idempotencyKey: 'payout-77');
// store $payout['internal_reference'], then wait for the PAYOUT webhook
```
