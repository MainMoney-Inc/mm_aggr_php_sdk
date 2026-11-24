# Match provider, customer lookup and KYC

Purpose: from a phone or account number, find the provider and (when supported)
the account holder before you charge or pay someone.

Prerequisites: a valid token. The contract names no scope for these calls.

## Match provider

`GET /transactions/customers/match-provider/`. Query: `account_number` (required),
`get_lookup` (also return account information when the provider supports it),
`operation_type` (restrict to providers you can route that operation through). Only
providers in your allowed countries are returned. Example: `243840000000` can match
the Orange DRC provider.

```php
$match = $client->customers->matchProvider('243840000000', getLookup: true, operationType: 'DEPOSIT');
```

The contract types the response as a customer-lookup response:

```json
{ "account_number": "243840000000", "is_active": true, "name": "Jane Doe", "account_type": "mobile_money", "additional_info": {} }
```

The contract does not say where the matched provider code appears in this payload;
the browser checkout widget reads it from the response of this call. Inspect the
actual response of your environment (`additional_info` / provider fields) before
relying on a key. `name` and `additional_info` are provider-dependent.

## Lookup

`POST /transactions/customers/lookup/`

```php
$info = $client->customers->lookup([
    'provider_code' => 'VODACOM_MPESA_COD', // required
    'account_number' => '243820000000',     // required
    'account_type' => 'mobile_money',       // optional, e.g. bank | mobile_money
]);
```

Response: `account_number`, `is_active`, `name`, `account_type`, `additional_info`.
`is_active` false means the account cannot receive transactions.

## KYC

`POST /transactions/customers/kyc/`

```php
$kyc = $client->customers->kyc([
    'provider_code' => 'VODACOM_MPESA_COD', // required
    'account_number' => '243820000000',     // required
    'account_type' => 'mobile_money',       // optional
]);
```

Response fields: `account_number`, `is_active`, `name`, `account_type`, `first_name`,
`last_name`, `dob`, `id_number`, `nationality`, `grade`, `barred`, `lookup_supported`,
`error_code`, `message`, `additional_info`. Treat this as personal data: store only
what you need. Which providers support KYC is not stated in the contract
(`lookup_supported` says so per call).

## Errors

400 invalid input, 401 bad token. Throws `ApiException` / `AuthenticationException`.

## Minimal example

```php
$match = $client->customers->matchProvider($phone, getLookup: true);
if (($match['is_active'] ?? true) === false) {
    exit('Account cannot receive funds');
}
echo $match['name'] ?? 'unknown holder';
```
