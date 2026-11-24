# Errors and exception handling

Purpose: know what the SDK throws and which fields to read.

## Response shapes (contract)

Most transaction and general endpoints answer with an envelope. On success the SDK
returns the envelope's `response_data` directly:

```json
{ "success": true, "response_code": 200, "response_data": {}, "message": "..." }
```

A business error:

```json
{ "success": false, "response_code": 400, "response_data": { "errors": { "field": ["message"] } }, "message": "Human-readable summary" }
```

Paginated list endpoints return `count`, `next`, `previous`, `results` at the top
level (not unwrapped). Token exchange and some auth failures use the default DRF
shapes, for example `{"client_id": ["Invalid client_id"]}` or
`{"detail": "Authentication credentials were not provided."}`.

## Exceptions

| Class | When |
| --- | --- |
| `AuthenticationException` | Token exchange failed, or a second 401 after re-exchange. |
| `ApiException` | Any HTTP status 400 or above, or `success: false`. |
| `WebhookSignatureException` | `verifyOrFail()` rejected a webhook. |
| `AggregatorException` | Common parent of all of the above. |

`ApiException` exposes `getMessage()` (the envelope `message`, or `detail`),
`getStatusCode()`, `getErrors()` (`response_data.errors`, per field) and
`getResponseBody()` (the decoded body).

The contract documents these statuses: 400 validation (also insufficient balance
on remittances), 401 unauthenticated, 403 missing scope or inactive/foreign seller,
404 unknown reference on a status check, 409 idempotency conflict (and payment-link
conflicts). It lists no other error codes; do not invent any.

## Minimal example

```php
use MainMoney\Aggregator\Exception\ApiException;
use MainMoney\Aggregator\Exception\AuthenticationException;

try {
    $deposit = $client->deposits->create($payload, idempotencyKey: $key);
} catch (ApiException $e) {
    match (true) {
        $e->getStatusCode() === 409 => /* same key, different body, or still in progress */ null,
        $e->getStatusCode() === 403 => /* scope missing or seller inactive */ null,
        default => error_log($e->getMessage().' '.json_encode($e->getErrors())),
    };
} catch (AuthenticationException $e) {
    // bad credentials
}
```

Never show `$e->getMessage()` or provider text to end customers verbatim without
review; log it server-side and show your own message. A provider failure on a
deposit or payout is reported as `PENDING` (HTTP 202), not as a final failure; see
[status.md](status.md).
