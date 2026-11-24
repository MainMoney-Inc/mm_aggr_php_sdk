# Payment links

A payment link is a hosted payment page. You create a link for a fixed amount
and currency, send the returned `url` to your customer (email, chat, QR code),
and the customer pays there with a card or mobile money. You do not need to
build a checkout.

Requirements: the API key must have the `PAYMENT_LINK` scope. The pinned contract lists only `X-Merchant-Account` as an optional header on these endpoints (no `Idempotency-Key`).

```php
use MainMoney\Aggregator\Client;

$client = new Client(clientId: getenv('MM_CLIENT_ID'), secret: getenv('MM_API_SECRET'), test: true);
```

## Create

```php
$link = $client->paymentLinks->create(
    [
        'reference' => 'INV-1042',      // required, your unique reference (max 100)
        'amount' => '25.00',            // required, decimal string
        'currency' => 'USD',            // required, 3 letters
        'title' => 'Invoice 1042',      // optional (max 140)
        'description' => 'Consulting',  // optional (max 2000)
        'methods' => ['card', 'mobile_money'], // optional; empty = every method available to you
        'expires_at' => '2026-12-31T23:59:59Z', // optional ISO-8601
        'single_use' => true,           // optional, default true
        'redirect_url' => 'https://shop.example/thanks', // optional
        'customer_name' => 'Jane Doe',  // optional
        'customer_email' => 'jane@example.com',
        'customer_phone' => '243820000000',
        'metadata' => ['order_id' => 1042],
    ],
    idempotencyKey: 'invoice-1042-link',
);

$link['url'];    // public URL the customer pays at
$link['id'];     // use with get() / cancel()
$link['status']; // ACTIVE, PAID, EXPIRED or CANCELLED
```

Fields `reference`, `amount` and `currency` are required. The amount comes only
from the link, never from the customer. Do not mix currencies.

## List, get, cancel

```php
$page = $client->paymentLinks->list(['status' => 'ACTIVE']); // newest first
$page['count'];
$page['results']; // list of links (paginated: count, next, previous, results)

$link = $client->paymentLinks->get($id);

$link = $client->paymentLinks->cancel($id); // only an ACTIVE link; paid links cannot be cancelled
```

`list()` also accepts `page`, `page_size`, `ordering` and `search`.

## Using `url`

Send `$link['url']` to the customer. It is safe to share publicly. Keep your
API credentials on the server only.

## Expiry and single use

- `expires_at`: after this time the link stops accepting payments and its
  status becomes `EXPIRED`. Omit it for a link that does not expire.
- `single_use` (default `true`): the link closes after the first successful
  payment (status `PAID`). Set `false` to accept several payments on one link.
- `redirect_url`: where the customer is sent after paying.

## On behalf of a seller

Pass `merchantAccount` (a seller's account id) to any method to send it as the
`X-Merchant-Account` header. The link is then created for, and attributed to,
that seller (`account_id` in the response); on the other calls the seller must
own the link.

```php
$link = $client->paymentLinks->create($payload, merchantAccount: $sellerId);
$client->paymentLinks->list(['status' => 'ACTIVE'], merchantAccount: $sellerId);
$client->paymentLinks->get($id, merchantAccount: $sellerId);
$client->paymentLinks->cancel($id, merchantAccount: $sellerId);
```

The API answers 403 when the seller is unknown, is not yours or is not active.

## Errors

Failures throw `MainMoney\Aggregator\Exception\ApiException`
(`getStatusCode()`, `getErrors()`).

- 400: invalid request (missing or malformed fields).
- 409: conflict, for example reusing a `reference`, or cancelling a link that
  is already paid.
- 401: bad credentials (`AuthenticationException`); 403: missing scope or
  inactive seller.

## Knowing a link was paid

- Poll `$client->paymentLinks->get($id)` until `status` is `PAID` (and
  `paid_at` is set), or filter `list(['status' => 'PAID'])`.
- Or handle your webhook: the payment is reported with the usual transaction
  webhook; verify it with `$client->webhooks->verify($rawBody, $signature, $secret)`
  and match on your `reference` / `metadata`. Treat the link status from
  `get()` as the source of truth.
