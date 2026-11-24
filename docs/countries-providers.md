# Countries and providers

Purpose: find where you may operate and through which provider. Both lists are
read-only and already filtered to your merchant's configuration. Allowing or
disallowing a country happens in the back office, not through the API.

Prerequisites: a valid token ([authentication.md](authentication.md)). The contract
names no scope for these reads.

Cache them (for example 24 hours) and refresh when your credentials or configuration
change.

## Countries

`GET /manage/general/countries/` is the effective allow-list. Send transactions only
for these countries.

```php
$page = $client->countries->list(['search' => 'congo', 'page_size' => 50]);
```

Query: `ordering`, `page`, `page_size`, `search`. Response (paginated):

```json
{
  "count": 1, "next": null, "previous": null,
  "results": [
    { "code": "CD", "name": "DR Congo", "iso3_code": "COD", "phone_code": "243", "flag": "https://.../cd.svg" }
  ]
}
```

`flag` may be null. (Example values are illustrative; field names are from the contract.)

## Providers (financial entities)

`GET /manage/general/financial-entities/`, restricted to your effective countries.

```php
$providers = $client->providers->list([
    'country' => 'CD',            // optional
    'entity_type' => 'mobile_money', // optional; the contract does not list the accepted values
    'operation_type' => 'DEPOSIT',   // optional: only providers you can run that operation on
]);
```

Query: `country`, `entity_type`, `operation_type`, `ordering`, `page`, `page_size`,
`search`. Each result:

```json
{ "code": "VODACOM_MPESA_COD", "name": "...", "entity_type": "...", "country_code": "CD",
  "currency_code": "CDF", "accepted_currencies": ["CDF", "USD"], "logo": null, "is_active": true }
```

Use `code` as `provider_code` in deposits, payouts and so on. `accepted_currencies`
are the values allowed for `payment_currency` (see [fees-limits-pricing.md](fees-limits-pricing.md)).

## Checkout preferences

`GET /manage/general/checkout-preferences/` returns branding and the checkout setup:
`primary_color`, `secondary_color`, `accent_color`, `background_color`, `locale`,
`logo`, `fee_bearer` (`MERCHANT` or `CUSTOMER`) and `payment_methods` (any of
`mobile_money`, `card`, `mainmoney`, in display order).

```php
$prefs = $client->checkoutPreferences->get();
```

## Errors

401 bad token (`AuthenticationException`); other failures throw `ApiException`
([errors.md](errors.md)).

## Minimal example

```php
$countries = array_column($client->countries->list()['results'], 'code');
$providers = $client->providers->list(['country' => $countries[0], 'operation_type' => 'DEPOSIT']);
foreach ($providers['results'] as $p) {
    echo $p['code'], ' ', implode(',', $p['accepted_currencies']), PHP_EOL;
}
```
