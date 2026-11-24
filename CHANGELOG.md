# Changelog

## Unreleased

## 0.2.1

- Added resources covering the rest of the pinned contract: `sellers` (create, list, get, update, countries, KYB read/edit/start/submit, onboarding link create/revoke), `transfers` (create, list, get), `pricing`, `cardDeposits->validatePayment()` and `cardPayouts->createBusinessMerchantAccount()`.
- Optional `merchantAccount` argument (sent as `X-Merchant-Account`) on deposits (`create`, `validatePayment`), payouts (all three), refunds, remittances, `status->check()`, `wallets->list()`, `transactions->list()` and `reports`. Backward compatible: it is always the last, optional argument.
- Transport: `patch()` and `delete()`; an empty request body is now sent as `{}` instead of `[]`.
- Docs: one guide per flow under `docs/`, indexed in `docs/README.md`.

## 0.2.0

- Added `paymentLinks` resource: `create`, `list`, `get`, `cancel` for the Payment Links API (API key scope `PAYMENT_LINK`).
- Every payment link method accepts an optional `merchantAccount` to send `X-Merchant-Account` (act on behalf of a seller).
- Docs: `docs/payment-links.md` and README section.

## 0.1.3

- Security: the Laravel example requires `laravel/framework` ^12.71.
- Workflows declare least-privilege `permissions: contents: read` (CodeQL).
- README and Laravel example: derive the `Idempotency-Key` from the checkout session, not the reference.

## 0.1.1

- Relicensed under the MIT License.
- First release published to Packagist (`mainmoney/mm-aggr-php-sdk`).
