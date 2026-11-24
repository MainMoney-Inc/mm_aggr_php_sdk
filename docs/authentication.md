# Authentication and token exchange

Purpose: authenticate server-side calls. The merchant API uses a Bearer access
token obtained from `POST /auth/tokens/exchange/`. There is no `X-API-KEY` header.

Prerequisites: an application `client_id` and API key `secret` from the
aggregator portal. Keep both on your server only; never in browser or plugin
JavaScript. API keys carry scopes; a call without the needed scope answers HTTP
403. The contract names the `PAYMENT_LINK` and `BUSINESS_PAYOUT` scopes; for other
operations it does not list the scope names.

## What the SDK does

`Client` exchanges the credentials lazily on the first call, caches the token until
shortly before `expires_at`, and re-exchanges after a 401 (one retry). You never
handle the token yourself.

```php
use MainMoney\Aggregator\Client;

$client = new Client(
    clientId: getenv('MM_CLIENT_ID'),
    secret: getenv('MM_API_SECRET'),
    test: true,                 // https://testaggregator.mainmoney.net/api/v1/ ; omit for production
    // baseUri: 'https://...',  // override the host (the /api/v1/ suffix is added if missing)
    // timeout: 30.0,           // seconds
    // tokenExpiresIn: 3600,    // token lifetime, 60..86400
);
```

Production is `https://aggregator.mainmoney.net/api/v1/`.

## Wire format (for reference)

Request (no `Authorization` header):

```json
{ "client_id": "<client id>", "secret": "<secret>", "expires_in": 3600 }
```

Response:

```json
{ "access_token": "<token>", "token_type": "Bearer", "expires_in": 3600, "expires_at": "2026-01-01T12:00:00Z" }
```

`expires_in` is optional (default 3600, min 60, max 86400). Every other call sends
`Authorization: Bearer <access_token>`.

## Error cases

- Invalid credentials: the exchange fails and the SDK throws
  `MainMoney\Aggregator\Exception\AuthenticationException` ("Token exchange failed").
- A 401 on a normal call triggers one re-exchange; a second 401 throws
  `AuthenticationException`.
- Missing scope or inactive application: HTTP 403, thrown as `ApiException`.

## Minimal example

```php
use MainMoney\Aggregator\Client;
use MainMoney\Aggregator\Exception\AuthenticationException;

$client = new Client(getenv('MM_CLIENT_ID'), getenv('MM_API_SECRET'), test: true);

try {
    $countries = $client->countries->list(); // first call performs the exchange
} catch (AuthenticationException $e) {
    error_log('Check MM_CLIENT_ID / MM_API_SECRET');
}
```
