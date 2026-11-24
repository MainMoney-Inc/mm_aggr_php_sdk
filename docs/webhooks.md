# Webhooks

Purpose: be told when a transaction changes state. Merchants only receive webhooks:
there is no REST endpoint to register or verify them. The URL and the secret are
configured in the aggregator admin; an optional rule sets retries.

Prerequisites: a public HTTPS endpoint and your webhook secret. Never verify in the
browser.

## Request you receive

`POST` to your URL with:

| Header | Meaning |
| --- | --- |
| `Content-Type` | `application/json` |
| `User-Agent` | `MM-Aggregator-Webhook/1.0` |
| `X-Webhook-Event-ID` | event UUID |
| `X-Webhook-Provider` | financial entity code |
| `X-Webhook-Type` | `DEPOSIT`, `PAYOUT`, `REMITTANCE`, `REFUND`, `STATUS_UPDATE`, `DIRECT_DEBIT`, `DEPOSIT_PROFILE`, `SETTLEMENT` |
| `X-Webhook-Timestamp` | ISO-8601 |
| `X-Webhook-Signature` | hex HMAC-SHA256 (when a secret is set) |

There is no HTTP Basic Auth and no `X-Callback-Signature`.

Body:

```json
{
  "webhook_event_id": "uuid", "type": "DEPOSIT", "provider_code": "MPESA_KE",
  "transaction_id": "...", "provider_transaction_id": "...", "timestamp": "...",
  "status": "SUCCESS", "amount": "100.00", "currency": "KES",
  "merchant_reference": "ORDER-12345", "internal_reference": "...",
  "metadata": {}, "error_message": null
}
```

For a deposit paid in another currency the payload reports the charged `amount` and
`currency` plus `original_amount` and `original_currency`.

## Verify the signature

The signature is HMAC-SHA256 (hex) of the exact raw body (canonical JSON with sorted
keys, no spaces) with your webhook secret. Verify the raw bytes before parsing.

```php
$ok = $client->webhooks->verify($rawBody, $signatureHeader, $secret); // bool, constant-time
$client->webhooks->verifyOrFail($rawBody, $signatureHeader, $secret); // throws WebhookSignatureException
```

Both return false/throw when the signature or the secret is empty. If no secret is
configured for your application the aggregator sends no signature; the contract does
not say whether you may accept unsigned events, so configure a secret.

## Respond, retries, idempotence

Answer HTTP 2xx once the event is stored. Retries follow the matching webhook rule
(`max_retries`, `retry_delay_seconds`); the contract gives no other schedule. The
same event can arrive more than once: deduplicate on `webhook_event_id` (or
`merchant_reference` plus `status`) and keep the handler idempotent. The status check
([status.md](status.md)) is the fallback.

## Minimal example (plain PHP)

```php
$raw = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

if (!$client->webhooks->verify($raw, $signature, getenv('MM_WEBHOOK_SECRET'))) {
    http_response_code(400);
    exit;
}
$event = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
// if (already_processed($event['webhook_event_id'])) { http_response_code(200); exit; }
update_order($event['merchant_reference'], $event['status']);
http_response_code(200);
```
