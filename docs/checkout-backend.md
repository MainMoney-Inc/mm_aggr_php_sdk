# Backend for the browser checkout widget

Purpose: the JS/TS checkout widget runs in the browser and must never hold your API
key. It calls a backend that **you** implement with this SDK; that backend calls the
aggregator. Prerequisites: a valid token on the server, a per-checkout session token
you mint, and the widget's `merchantBackendUrl`.

A runnable Laravel example is in [../examples/laravel](../examples/laravel): see
`app/Http/Controllers/ShopController.php` (session, webhook) and
`app/Support/ProxyService.php` (the routes below).

## Routes to implement (relative to `merchantBackendUrl`)

| Method | Path | SDK call |
| --- | --- | --- |
| GET | `/countries` | `$client->countries->list()` |
| GET | `/providers?country=` | `$client->providers->list($query)` |
| GET | `/match-provider?account_number=&get_lookup=true` | `$client->customers->matchProvider($n, true)` |
| GET | `/amount-limits` | `$client->amountLimits->list($query)` |
| POST | `/fees/simulate` | `$client->fees->simulate($body)` |
| GET | `/checkout-preferences` | `$client->checkoutPreferences->get()` |
| POST | `/deposits` | `$client->deposits->create($body, $key)` |
| POST | `/deposits/validate` | `$client->deposits->validatePayment($body)` |
| POST | `/payouts` | `$client->payouts->create($body, $key)` |
| GET | `pollUrl` | `$client->status->check($operation, $reference)` |

Authorize each call with your own short-lived session token (the widget sends
`Authorization: Bearer <clientToken>`; this is **not** the aggregator token). Take the
`reference` and, when the amount is locked, the `amount` from your server-side session,
never from the browser. Do not put the aggregator `client_id`/`secret` or token in
any response.

## Idempotency

The widget no longer sends `Idempotency-Key` by default (it does only if the page sets
`idempotencyHeader: true`). Derive the key on the server, once per checkout session:

```php
$key = 'mm-'.substr(hash('sha256', $session->token), 0, 32);
$deposit = $client->deposits->create($payload, $key);
```

See [idempotency.md](idempotency.md): a refreshed page (new session) is a new attempt, a
double submit inside one session replays the first answer.

## CORS

The browser calls your backend cross-origin, so answer the preflight for your site's
exact origin (scheme, host and port; not `*` when `Authorization` is sent):

| Header | Value |
| --- | --- |
| `Access-Control-Allow-Origin` | `https://shop.example` |
| `Access-Control-Allow-Methods` | `GET, POST, OPTIONS` |
| `Access-Control-Allow-Headers` | `Content-Type, Authorization` (add `Idempotency-Key` only if the page sets `idempotencyHeader: true`) |

Laravel (`config/cors.php`):

```php
return [
    'paths' => ['*'],
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_origins' => ['https://shop.example'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'supports_credentials' => false,
];
```

A wildcard `*` in `Access-Control-Allow-Headers` does not cover `Authorization`; list it
explicitly. A CORS error on `POST /deposits` while the GET lookups work usually means
the preflight rejects a header or the origin (`http` vs `https`, `localhost` vs
`127.0.0.1`, port).

## Laravel proxy sketch

```php
Route::match(['GET', 'POST'], '/payments/{route?}', function (Request $request, string $route = '') {
    $token = trim(substr((string) $request->header('Authorization', ''), 7));
    $session = SessionStore::get($token);                 // your own store
    abort_if($session === null, 401);

    $client = new \MainMoney\Aggregator\Client(env('MM_CLIENT_ID'), env('MM_API_SECRET'), test: true);

    try {
        $result = match ([$request->method(), trim($route, '/')]) {
            ['GET', 'countries'] => $client->countries->list(),
            ['GET', 'providers'] => $client->providers->list($request->query()),
            ['POST', 'fees/simulate'] => $client->fees->simulate($request->json()->all()),
            ['POST', 'deposits'] => $client->deposits->create(
                ['reference' => $session->reference] + $request->json()->all(),
                'mm-'.substr(hash('sha256', $session->token), 0, 32),
            ),
            default => abort(404),
        };
    } catch (\MainMoney\Aggregator\Exception\ApiException $e) {
        return response()->json(['message' => 'Payment request failed', 'errors' => $e->getErrors()], $e->getStatusCode() ?: 400);
    }

    return response()->json($result);
})->where('route', '.*');
```

Note the order in the merge: `['reference' => ...] + $body` keeps the server-side
`reference` even if the browser sends one. Do not return raw exception messages
to the browser in production.

## Minimal checklist

1. Mint a session (reference, locked amount, token) when the page loads.
2. Implement the routes above with the SDK; never expose aggregator credentials.
3. Allow `Content-Type, Authorization` in CORS for your exact origin.
4. Send the idempotency key from the server.
5. Confirm the outcome with the [webhook](webhooks.md); polling ([status.md](status.md)) is the fallback.
