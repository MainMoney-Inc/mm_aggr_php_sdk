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

## Quick start

```php
use MainMoney\Aggregator\Client;

$client = new Client(
    baseUri: 'https://your-aggregator.example/api/v1/',
    apiKey: getenv('MM_API_KEY'),
);
```

Configure credentials from your environment. See merchant API docs at
`/api/v1/docs/merchants/` on your aggregator host.

Payment methods (deposits, payouts, status, refunds) will be added in a later
release. Do not send merchant API keys to the browser.

## License

Copyright (c) 2026 MainMoney SARL. Licensed under the PolyForm Noncommercial
License 1.0.0. Non-commercial use is allowed. Commercial use requires
permission from MainMoney SARL. See [LICENSE](LICENSE).

Want to contribute? See [CONTRIBUTING.md](CONTRIBUTING.md).
