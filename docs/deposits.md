# Deposits

Purpose: collect money from a customer (mobile money or provider flow). The deposit
is credited to your merchant wallet upon success.

Prerequisites: a valid token; an allowed country and a provider that supports
deposits ([countries-providers.md](countries-providers.md)). The contract names no
scope for deposits.

## Create

`POST /transactions/deposits/` (HTTP 202 on acceptance). Optional `Idempotency-Key`
([idempotency.md](idempotency.md)) and optional `X-Merchant-Account`
([rails.md](rails.md)).

```php
$deposit = $client->deposits->create(
    [
        'provider_code' => 'VODACOM_MPESA_COD',  // required
        'reference' => 'ORDER-1042',             // required, your merchant_reference, unique per application
        'amount' => '100.00',                    // required, decimal string
        'currency' => 'USD',                     // required, order currency
        'customer_phone' => '243820000000',      // required, E.164 digits
        'payment_currency' => 'CDF',             // optional, see fees-limits-pricing.md
        'customer_name' => 'Jane Doe',           // optional
        'customer_email' => 'jane@example.com',  // optional, receipt on success
        'fees' => '1.00',                        // optional merchant fee (if enabled for you)
        'fee_bearer' => 'CUSTOMER',              // optional override
        'application_fee' => '0.50',             // optional, platforms acting for a seller
        'application_fee_percent' => null,       // optional, not together with application_fee
        'profile_reference' => null,             // for providers that need deposit profiles
        'callback_url' => 'https://shop.example/hooks/mm', // optional
        'metadata' => ['order_id' => 1042],      // optional
    ],
    idempotencyKey: 'checkout-session-9f2c',
    merchantAccount: null,                       // seller id, platforms only
);
```

Response (the SDK returns the envelope's `response_data`):

```json
{
  "status": "PENDING",
  "transaction_id": "...", "internal_reference": "...", "external_reference": "...",
  "merchant_reference": "ORDER-1042", "timestamp": "2026-01-01T12:00:00Z",
  "provider_reference": null, "message": "...", "error_code": null, "error_message": null,
  "data": {}, "processing_time_ms": 412
}
```

`status` is `SUCCESS`, `PENDING`, `FAILED`, etc. The customer normally approves on
their phone, so expect `PENDING`: settle it with a [webhook](webhooks.md) or
[status polling](status.md). A provider failure on a deposit is never reported as
final (HTTP 202, `PENDING`); only failures before the provider call (validation,
limits) are final `FAILED`. The contract says the `fees` and `fee_bearer` rules are
in [fees-limits-pricing.md](fees-limits-pricing.md).

## Card: validate payment

Some card flows need a second step with an authenticator token. The contract
describes both endpoints as "Validate deposit payment (authenticator token)" and
documents no request body, only a deposit response. Send what your provider flow
returned to you.

```php
$result = $client->deposits->validatePayment($payload);       // POST /transactions/deposits/validate-payment/  (accepts merchantAccount)
$result = $client->cardDeposits->validatePayment($payload);   // POST /transactions/card-deposits/validate-payment/
```

Both return a deposit response (same fields as above). The card path lists no
`X-Merchant-Account` in the contract.

## Errors

- 400 validation: `getErrors()` per field (missing field, unsupported country, `fees`
  not enabled, `no_quote` for `payment_currency`).
- 409 idempotency conflict.
- 401 / 403 as in [errors.md](errors.md).
- A failed deposit keeps its `reference`; retry with a new one.

## Minimal example

```php
use MainMoney\Aggregator\Exception\ApiException;

try {
    $deposit = $client->deposits->create([
        'provider_code' => 'VODACOM_MPESA_COD',
        'reference' => 'ORDER-1042',
        'amount' => '100.00',
        'currency' => 'USD',
        'customer_phone' => '243820000000',
    ], idempotencyKey: 'checkout-session-9f2c');
    echo $deposit['status']; // usually PENDING; wait for the webhook
} catch (ApiException $e) {
    error_log($e->getMessage().' '.json_encode($e->getErrors()));
}
```
