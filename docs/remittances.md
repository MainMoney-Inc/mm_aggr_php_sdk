# Remittances

Purpose: move value between two merchants, across currencies. The sender's wallet is
debited and the receiver's credited upon success; balance is checked first.

Prerequisites: a valid token, sufficient balance. The contract names no scope.

`POST /transactions/remittances/` (HTTP 201). Optional `Idempotency-Key` and optional
`X-Merchant-Account` ([rails.md](rails.md)).

```php
$remittance = $client->remittances->create([
    'provider_code' => 'VODACOM_MPESA_COD',   // required
    'reference' => 'REM-501',                 // required, unique per application
    'receiver_merchant_id' => '0d9a6a0e-0000-4000-8000-0000000000bb', // required, uuid
    'source_amount' => '100.50',              // required, at most 2 decimals
    'source_currency' => 'USD',               // required
    'destination_amount' => '270000.00',      // required, checked against the active exchange rate
    'destination_currency' => 'CDF',          // required
    'destination_account' => '243820000000',  // required
    'destination_name' => 'Jane Doe',         // optional
    'purpose' => 'Family support',            // optional
    'purpose_code' => null,                   // optional
    'callback_url' => null,                   // optional
    'metadata' => [],                         // optional
], idempotencyKey: 'rem-501');
```

Response: the same transaction response as deposits (`status`, `transaction_id`,
`internal_reference`, `external_reference`, `merchant_reference`, `timestamp`,
`provider_reference`, `message`, `error_code`, `error_message`, `data`,
`processing_time_ms`).

Errors: 400 for validation, an amount that does not match the active exchange rate,
or insufficient balance; 401; 403; 409. Source and destination amounts are in
different currencies: never add them together.

Minimal example:

```php
$r = $client->remittances->create($payload, idempotencyKey: 'rem-501');
echo $r['status'];
```
