---
name: add-php-api-resource
description: Add a merchant API resource to the MainMoney PHP SDK from OpenAPI
---

# Add a PHP API resource

1. Read the pinned contract `contrib/contract/openapi/merchants.openapi.yaml`
   (and `contrib/contract/resources.md`). Cross-check live
   `/api/v1/schema/merchants/` if the pin may be behind. Do not invent endpoints.
2. Add a typed client method under `src/` with PHPUnit coverage.
3. Keep README user-facing (how to call the method). Put test/setup notes in CONTRIBUTING.md.
