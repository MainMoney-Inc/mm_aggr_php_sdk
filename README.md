# MainMoney PHP SDK

PHP client for the MainMoney aggregator merchant API. Install this package in
your PHP application (Laravel, Symfony, WordPress, WooCommerce, or plain PHP).

WordPress and WooCommerce plugins from MainMoney use this SDK on the server.
Browser checkout uses the [JS/TS frontend SDK](https://github.com/MainMoney-Inc/mm_aggr_js_sdk) and still requires this (or another) backend SDK.

## Requirements

- PHP 8.2 or later (8.4 or 8.5 recommended)
- Composer
- A merchant application on MM Aggregator

## Install

```bash
composer require mainmoney/mm-aggr-php-sdk
```

Until the package is on Packagist, require the GitHub repository:

```bash
composer require mainmoney/mm-aggr-php-sdk:dev-main
```

## Quick start

```php
use MainMoney\Aggregator\Client;

$client = new Client(
    clientId: getenv('MM_CLIENT_ID'),
    secret: getenv('MM_API_SECRET'),
    test: true, // https://testaggregator.mainmoney.net — omit for production
);

$deposit = $client->deposits->create(
    [
        'provider_code' => 'VODACOM_MPESA_COD',
        'reference' => 'ORDER-123',
        'amount' => '100.00',
        'currency' => 'USD',
        'customer_phone' => '243820000000',
    ],
    idempotencyKey: 'ORDER-123',
);
```

Defaults: production `https://aggregator.mainmoney.net/api/v1/`, test
`https://testaggregator.mainmoney.net/api/v1/`. Pass `baseUri` only to override.
Configure credentials from your environment. Merchant API docs:
`/api/v1/docs/merchants/` on the aggregator host.

Exchange `client_id` and `secret` for a Bearer access token is handled by the
SDK. There is no `X-API-KEY` header. Reuse the same `reference` and optional
`Idempotency-Key` when retrying a create. Amounts are decimal strings; do not
mix currencies.

Verify inbound webhooks with `$client->webhooks->verify($rawBody, $signature, $secret)`.

Do not send merchant API keys to the browser.

## License

Copyright (c) 2026 MainMoney SARL. Licensed under the PolyForm Noncommercial
License 1.0.0. Non-commercial use is allowed. Commercial use requires
permission from MainMoney SARL. See [LICENSE](LICENSE).

## Examples

A runnable Laravel mini-shop lives in [examples/laravel](examples/laravel).
Pair it with a
[JS/TS frontend example](https://github.com/MainMoney-Inc/mm_aggr_js_sdk/tree/main/examples).

Want to contribute? See [CONTRIBUTING.md](CONTRIBUTING.md).
