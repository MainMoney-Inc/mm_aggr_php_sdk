# Wallets, transactions and reports

Purpose: balances, transaction history and aggregates for reconciliation.
Prerequisites: a valid token. The contract names no scope. Every method here accepts
an optional `merchantAccount` (seller id) to read a seller's data
([rails.md](rails.md)). Amounts are per currency: never sum across currencies.

## Wallets (balances)

`GET /manage/merchant-admin/wallets/`. Query: `country`, `currency`, `ordering`,
`page`, `page_size`, `search`, `status`.

```php
$wallets = $client->wallets->list(['currency' => 'USD']);
foreach ($wallets['results'] as $w) {
    echo $w['currency'], ' available ', $w['available_balance'], ' (reserved ', $w['reserved_balance'], ')', PHP_EOL;
}
```

Result fields: `id`, `merchant_id`, `merchant_name`, `currency`, `country`,
`is_active`, `balance`, `reserved_balance`, `available_balance`, `created_at`,
`updated_at`. Paginated (`count`, `next`, `previous`, `results`).

## Transactions

`GET /manage/merchant-admin/transactions/{deposit|payout|refund|remittance|settlement}/`.
Query: `amount_min`, `amount_max`, `country`, `currency`, `date_from`, `date_to`,
`internal_reference`, `merchant_reference`, `ordering`, `page`, `page_size`,
`provider_code`, `search`, `status`.

```php
$page = $client->transactions->list('deposit', ['status' => 'SUCCESS', 'date_from' => '2026-01-01', 'page_size' => 100]);
```

Result fields: `id`, `operation_type`, `internal_reference`, `merchant_reference`,
`external_reference`, `provider_transaction_id`, `amount`, `currency`,
`provider_code`, `country`, `status`, `status_reason`, `created_at`, `completed_at`.
The contract does not state the format of `date_from`/`date_to`.

## Reports

`GET /manage/merchant-admin/reports/summary/` and `/reports/charts/`. Query:
`currency`, `date_from`, `date_to`, `operation_type`, `provider_code`.

```php
$summary = $client->reports->summary(['currency' => 'USD']);
// totals_by_provider: provider_code, currency, count, total_amount, success_count
// top_transactions: operation_type, amount, currency, provider_code, merchant_reference, internal_reference, created_at

$charts = $client->reports->charts(['currency' => 'USD']);
// volume_by_day: date, currency, count, total_amount
```

## Errors

401, 403 (inactive/foreign seller when `merchantAccount` is set), 400. See
[errors.md](errors.md).

## Minimal example

```php
$balance = array_filter(
    $client->wallets->list()['results'],
    static fn (array $w): bool => $w['currency'] === 'USD',
);
```
