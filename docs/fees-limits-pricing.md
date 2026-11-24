# Amount limits, fee simulation and pricing

Purpose: show the customer what they pay, and reject out-of-range amounts before
calling a create endpoint. Prerequisites: a valid token. The contract names no
scope for these reads. Amounts are decimal strings; never mix currencies.

## Amount limits

`GET /manage/general/amount-limits/` (cacheable). Query: `currency`,
`financial_entity_code`, `operation_type`, `ordering`, `page`, `page_size`, `search`.

```php
$limits = $client->amountLimits->list(['financial_entity_code' => 'VODACOM_MPESA_COD', 'currency' => 'USD', 'operation_type' => 'DEPOSIT']);
```

Each result: `id`, `financial_entity_code`, `operation_type`, `currency`,
`amount_min`, `amount_max` (either may be null), `effective_from`,
`effective_until`, `is_active`, `description`.

## Who pays the fees

`amount` is always the goods amount. A merchant's own fee is the separate `fees`
field, accepted on deposits only, and only if the back office enabled it for your
account (otherwise HTTP 400). Platform fees are computed on `B = amount + fees`
(deposit) or `B = amount` (payout). `fee_bearer` (`MERCHANT` default from your
settings, or `CUSTOMER`) can be overridden per request:

| Operation | `fee_bearer` | Provider processes | Merchant wallet |
| --- | --- | --- | --- |
| Deposit | MERCHANT | customer pays B | credited B, fees C debited |
| Deposit | CUSTOMER | customer pays B + C | credited B + C, fees C debited |
| Payout | MERCHANT | recipient gets `amount` | debited `amount + C` |
| Payout | CUSTOMER | recipient gets `amount - C` | debited `amount` |

A payout whose fees would take the whole amount with `CUSTOMER` is rejected.

## Fee simulation

`POST /manage/general/fees/simulate/`

```php
$sim = $client->fees->simulate([
    'provider_code' => 'VODACOM_MPESA_COD', // required
    'operation_type' => 'DEPOSIT',          // required (OperationType enum, e.g. DEPOSIT, PAYOUT)
    'amount' => '100.00',                   // required
    'currency' => 'USD',                    // optional
    'fees' => '1.00',                       // optional merchant fee (deposits)
    'fee_bearer' => 'CUSTOMER',             // optional override
]);
```

Response fields (all strings unless noted): `provider_code`, `operation_type`, `amount`,
`currency`, `provider_fee`, `our_fee`, `merchant_additional_fee`, `promotion_discount`,
`total_merchant_fee`, `net_amount`, `fee_bearer`, `merchant_fee`, `base_amount`,
`provider_fee_declarative` (bool), `charged_fees`, `provider_amount` (what the
provider processes), `merchant_wallet_effect` (negative for payouts), the checkout fee
box `merchant_receives`, `processing_fee`, `total_to_pay`, the rule ids
(`provider_fee_rule_id`, `our_fee_rule_id`, `promotion_rule_id`,
`merchant_additional_rule_ids`) and `quote`.

Example from the contract's own numbers: `amount` 100, `fees` 1, platform fees 3 on
101. `CUSTOMER`: the customer pays 104, the merchant nets 101. `MERCHANT`: the customer
pays 101, the merchant nets 98.

### Paying in another currency (`payment_currency`)

`currency` stays the order currency; `payment_currency` is what the customer pays in,
chosen among the provider's `accepted_currencies`. The simulation is then expressed
in `payment_currency` with a quote:

```php
$sim = $client->fees->simulate([
    'provider_code' => 'VODACOM_MPESA_COD', 'operation_type' => 'DEPOSIT',
    'amount' => '100.00', 'currency' => 'USD', 'payment_currency' => 'CDF',
]);
$sim['quote']; // from_currency, to_currency, rate, source, converted_amount
```

With no active exchange rate the call answers HTTP 400 with
`errors.payment_currency: ["no_quote: ..."]`: ask the customer to pay in the order
currency. A deposit accepts the same `payment_currency`; its status and webhook then
report the charged `amount`/`currency` plus `original_amount` and `original_currency`.

## Fee schedule

`GET /manage/general/fees/` lists the configured rules. Query: `country`, `currency`,
`financial_entity_code`, `ordering`, `page`, `page_size`, `search`. Results include
`scope`, `operation_type`, `fee_type`, `amount_type`, `value`, `composition_mode`,
`currency`, `financial_entity_code`, `effective_from`, `effective_until`, `is_active`,
`name`, `description`.

```php
$rules = $client->fees->list(['financial_entity_code' => 'VODACOM_MPESA_COD']);
```

## Pricing

`GET /manage/general/pricing/`: what you pay now per operation type (no transaction
created). Optional query `financial_entity_code`, `operation_type`.

```php
$pricing = $client->pricing->get(['operation_type' => 'PAYOUT']);
foreach ($pricing['items'] as $item) {
    echo $item['operation_type'], ' total ', $item['total_merchant_fee'], PHP_EOL;
}
```

Item fields: `operation_type`, `provider_fee`, `provider_fee_declarative`, `our_fee`,
`merchant_additional_fee`, `promotion_discount`, `total_merchant_fee`, `charged_fees`,
and rule ids. The contract notes a live provider tariff override for BIF/USD when
the adapter supports it.

## Errors

400 (invalid input, no quote), 401. `ApiException` / `AuthenticationException`.

## Minimal example

```php
$sim = $client->fees->simulate(['provider_code' => $code, 'operation_type' => 'DEPOSIT', 'amount' => '25.00', 'currency' => 'USD']);
echo 'Customer pays ', $sim['total_to_pay'], ' ', $sim['currency'], PHP_EOL;
```
