# Status check and polling

Purpose: learn the current state of a transaction when a webhook is late or absent.
The aggregator queries the provider, updates the local record and returns it.

Prerequisites: a valid token. Optional `X-Merchant-Account` for a seller's
transaction ([rails.md](rails.md)). No `Idempotency-Key` on status checks.

`POST /transactions/status/check/{deposit|payout|refund|remittance}/`

```php
$tx = $client->status->check('deposit', 'ORDER-1042');
```

`reference` is your `merchant_reference` or the `internal_reference`. Response:

```json
{
  "status": "SUCCESS", "transaction_id": "...", "internal_reference": "...",
  "external_reference": "...", "merchant_reference": "ORDER-1042", "timestamp": "...",
  "provider_reference": null, "message": "...", "error_code": null, "error_message": null,
  "data": {}, "processing_time_ms": 120
}
```

`status` is `SUCCESS`, `PENDING`, `FAILED`, etc.; the contract does not list every
value. Poll while `PENDING`, stop on `SUCCESS` or `FAILED`, and log (do not guess)
any other value.

Errors: 404 for an unknown reference, 400, 401, 403 (seller).

## Polling example

```php
function waitForFinal(\MainMoney\Aggregator\Client $client, string $type, string $ref, int $attempts = 20): string
{
    for ($i = 0; $i < $attempts; $i++) {
        $status = (string) $client->status->check($type, $ref)['status'];
        if ($status !== 'PENDING') {
            return $status;
        }
        sleep(min(30, 3 + $i * 2)); // back off
    }

    return 'PENDING'; // keep waiting for the webhook
}
```

Prefer webhooks ([webhooks.md](webhooks.md)) and use polling as a fallback.
