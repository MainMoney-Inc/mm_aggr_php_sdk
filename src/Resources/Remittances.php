<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

/**
 * `create()` accepts an optional `$merchantAccount` (a seller id) sent as
 * `X-Merchant-Account`.
 */
final class Remittances extends Resource
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function create(
        array $payload,
        ?string $idempotencyKey = null,
        ?string $merchantAccount = null,
    ): array {
        return $this->transport->post(
            'transactions/remittances/',
            $payload,
            $this->idempotencyHeaders($idempotencyKey) + $this->merchantAccountHeaders($merchantAccount),
        );
    }
}
