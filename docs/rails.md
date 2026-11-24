# MainMoney Rails: sellers, KYB, acting on behalf, transfers

Purpose: for platforms (marketplaces, SaaS, aggregators) that onboard **sellers** and
transact for them. A seller's `id` is the value of the `X-Merchant-Account` header.

Prerequisites: a platform account with MainMoney Rails enabled. The contract names no
scope for the sellers and transfers endpoints.

## Countries a seller can be created in

`GET /sellers/countries/`

```php
$countries = $client->sellers->countries(); // list of { id, code, name }
```

Send one `id` (a uuid) as `country` when creating a seller.

## Create a seller

`POST /sellers/` (HTTP 201). `sub_type` is `LEGAL` (a business, verified before it can
transact) or `LOGIC` (wallets only, transacts at once). The seller gets a wallet for
each market of your account in its country.

```php
$seller = $client->sellers->create([
    'sub_type' => 'LEGAL',          // required: LEGAL | LOGIC
    'name' => 'Acme Shop',          // required
    'email' => 'owner@acme.example',// required
    'country' => $countries[0]['id'], // required, uuid
    'legal_name' => 'Acme SARL',    // optional
    'phone_number' => '243820000000', // optional (max 20)
    'registration_number' => 'RCCM-1', // optional
    'tax_id' => 'NIF-1',            // optional
]);
```

Response: `id`, `name`, `legal_name`, `email`, `sub_merchant_type`, `kyb_status`
(null for a seller with no verification), `is_operational` (active and, for a
business, `APPROVED`), `created_at`.

Other calls: `$client->sellers->list()` (`count` and `results`),
`->get($id)` (adds `country`, `notification_email`, `phone_number`,
`registration_number`, `tax_id` and the `kyb` case),
`->update($id, [...])` (PATCH: `name`, `legal_name`, `email`, `notification_email`,
`phone_number`, `registration_number`, `tax_id`; the country is fixed at creation).

## KYB (verification of a LEGAL seller)

1. Declare the data while the case is `DRAFT` (also again after `REJECTED` or
   `EXPIRED`):

   ```php
   $client->sellers->updateKyb($id, [
       'jurisdiction' => 'CD',   // ISO alpha-2, max 2
       'ubos' => [['name' => 'Jane Doe', 'ownership_pct' => '60', 'nationality' => 'CD', 'date_of_birth' => '1985-04-01']],
       // or 'no_ubo_reason' => '...' when there are no beneficial owners
   ]);
   $kyb = $client->sellers->kyb($id); // status, jurisdiction, ubos, no_ubo_reason, session_started, submitted_at, decided_at
   ```

2. Start the session (HTTP 201). Needs the jurisdiction and either owners or the
   reason there are none; the declared data is frozen afterwards. The response adds
   `capture_token` with `token`, `expires_at` and `upload_url`. Upload each document to
   `upload_url` as `multipart/form-data` with the header `X-Capture-Token: <token>`.
   The SDK does not upload documents; the contract does not describe the document
   fields or the list of required documents. Call `startKyb` again for a fresh token.

   ```php
   $session = $client->sellers->startKyb($id);
   $session['capture_token']['upload_url'];
   ```

3. Submit (HTTP 202). Refused with the missing documents named while one is missing;
   otherwise `status` becomes `IN_REVIEW`.

   ```php
   $client->sellers->submitKyb($id);
   ```

4. Poll `$client->sellers->kyb($id)['status']`: `APPROVED` (the seller can transact),
   `REJECTED` or `EXPIRED` (correct the data and start again). `DRAFT` and `IN_REVIEW`
   are the other values. The contract mentions no webhook for KYB; poll.

### Hosted onboarding link

Instead of collecting owners and documents yourself, give the business a hosted page:

```php
$link = $client->sellers->createOnboardingLink($id); // url (shown once), token, expires_at; replaces the previous link
$client->sellers->revokeOnboardingLink($id);          // ['revoked' => 1]
```

## Acting on behalf of a seller (`X-Merchant-Account`)

Pass the seller `id` as `merchantAccount` and the request runs as that seller: its
wallets, fees and limits apply and the record is attributed to it. The API answers 403
when the seller is unknown, is not yours or is not active.

```php
$seller = $sellerId;

$client->deposits->create($body, idempotencyKey: 'k1', merchantAccount: $seller);
$client->deposits->validatePayment($body, merchantAccount: $seller);
$client->payouts->create($body, 'k2', $seller);
$client->payouts->createBusiness($body, 'k3', $seller);
$client->payouts->createBusinessMerchantAccount($body, 'k4', $seller);
$client->refunds->create($body, 'k5', $seller);
$client->remittances->create($body, 'k6', $seller);
$client->status->check('deposit', 'ORDER-1', $seller);
$client->wallets->list([], $seller);
$client->transactions->list('deposit', [], $seller);
$client->reports->summary([], $seller);
$client->reports->charts([], $seller);
$client->paymentLinks->create($body, 'k7', $seller);
```

The argument is optional everywhere; omit it to act as your own account. Not in the
contract for `X-Merchant-Account`: transfers, sellers, customers, pricing, fees,
card deposits and card payouts.

On deposits and payouts a platform can take its own cut with `application_fee` (flat,
in the request currency) or `application_fee_percent` (of `amount`), not both,
capped by the platform's limit, and only when your account takes its own fee.

## Transfers between your wallet and a seller's

`POST /transactions/transfers/` (201 on create; 200 when the same `reference` is
replayed with the same details), `GET /transactions/transfers/` (query `reference`,
`sub_merchant_id`) and `GET /transactions/transfers/{id}/`.

```php
$transfer = $client->transfers->create([
    'reference' => 'TR-2001',                 // required, max 100, unique per platform
    'direction' => 'TO_SUB_MERCHANT',         // required: TO_SUB_MERCHANT | FROM_SUB_MERCHANT
    'sub_merchant_id' => $sellerId,           // required, uuid
    'amount' => '25.00',                      // required
    'currency' => 'USD',                      // required
    'description' => 'Top-up',                // optional, max 255
]);
$client->transfers->list(['sub_merchant_id' => $sellerId]);
$client->transfers->get($transfer['id']);
```

Response: `id`, `reference`, `direction`, `platform_merchant_id`, `sub_merchant_id`,
`amount`, `currency`, `description`, `channel` (`API` or `BACKOFFICE`), `created_at`.
Whether the list is paginated is not stated for this endpoint; read the response.

## Errors

400 validation (for example a missing `country`, KYB `submit` with missing documents,
a transfer without enough balance; the contract does not detail the transfer error
messages), 403 (seller not yours / inactive, or scope), 401. See [errors.md](errors.md).

## Minimal example (onboard and charge for a seller)

```php
$country = $client->sellers->countries()[0]['id'];
$seller = $client->sellers->create(['sub_type' => 'LOGIC', 'name' => 'Stall 12', 'email' => 's12@example.com', 'country' => $country]);

$client->transfers->create([
    'reference' => 'TR-1', 'direction' => 'TO_SUB_MERCHANT',
    'sub_merchant_id' => $seller['id'], 'amount' => '10.00', 'currency' => 'USD',
]);
$client->deposits->create($depositBody, idempotencyKey: 'sess-1', merchantAccount: $seller['id']);
```
