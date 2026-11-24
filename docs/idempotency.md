# Idempotency

Purpose: retry creates safely. Applies to deposits, payouts (all three paths),
remittances and refunds. (The contract lists no `Idempotency-Key` header on payment links; the SDK still accepts the argument there, but the contract does not promise replay.)

Two mechanisms exist:

1. `reference` (sent as the transaction's `merchant_reference`): unique per merchant
   application. Re-using one is a validation error.
2. Optional `Idempotency-Key` header (max 255 characters), per application:
   - same key and same body: the first status and body are replayed, the provider is
     not called again;
   - same key and a different body: HTTP 409;
   - same key while the first request is still running: HTTP 409.

There is no `Idempotency-Key` on GETs, token exchange, status checks, lookups or
fee simulation.

In the SDK pass it as the `idempotencyKey` argument:

```php
$deposit = $client->deposits->create($payload, idempotencyKey: 'checkout-session-9f2c');
```

Rules of thumb (from the contract):

- A key identifies one attempt, not one order. Retry the same create (same body)
  with the same key. When the body changes (new phone, other currency) use a new key.
- A failed deposit keeps its `reference` for good; a retry after a failure needs a new
  `reference` too (for example `ORDER-1042-r1`).
- For checkout flows derive the key from the checkout session, not from the cart or
  order, so a refreshed page starts a fresh attempt while a double submit replays.
- Make webhook handlers idempotent on `webhook_event_id` or `merchant_reference`.

Error case: HTTP 409 throws `ApiException` (`getStatusCode() === 409`).

```php
$key = 'checkout-'.$session->id;
try {
    $first = $client->deposits->create($body, idempotencyKey: $key);
    $again = $client->deposits->create($body, idempotencyKey: $key); // replay of $first
} catch (\MainMoney\Aggregator\Exception\ApiException $e) {
    if ($e->getStatusCode() === 409) { /* body changed under the same key */ }
}
```
