# Refunds

Purpose: reverse a previous deposit. Prerequisites: a valid token and the original
deposit's `reference`. The contract names no scope.

`POST /transactions/refunds/` (HTTP 201). Optional `Idempotency-Key` and optional
`X-Merchant-Account` ([rails.md](rails.md)).

```php
$refund = $client->refunds->create([
    'provider_code' => 'VODACOM_MPESA_COD',        // required
    'reference' => 'RF-1042',                      // required, new unique reference
    'original_transaction_id' => 'ORDER-1042',     // required, merchant reference of the original
    'amount' => '100.00',                          // required
    'currency' => 'USD',                           // required
    'customer_phone' => '243820000000',            // required
    'reason' => 'Customer cancelled',              // required
    'reason_code' => null,                         // optional
    'callback_url' => null,                        // optional
    'metadata' => [],                              // optional
], idempotencyKey: 'RF-1042');
```

Response: the common transaction response (`status`, `transaction_id`,
`internal_reference`, `external_reference`, `merchant_reference`, `timestamp`, ...).
Track it with a `REFUND` webhook or `$client->status->check('refund', 'RF-1042')`.

Errors: 400 (validation; the contract does not list the business rules such as
partial refunds or refund windows), 401, 403, 409.

Minimal example:

```php
$refund = $client->refunds->create($payload, idempotencyKey: $payload['reference']);
```
