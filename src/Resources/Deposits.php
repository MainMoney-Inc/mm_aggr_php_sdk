<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

/**
 * Mobile money / provider deposits. Both methods accept an optional
 * `$merchantAccount` (a seller id) sent as `X-Merchant-Account`.
 */
final class Deposits extends Resource
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
            'transactions/deposits/',
            $payload,
            $this->idempotencyHeaders($idempotencyKey) + $this->merchantAccountHeaders($merchantAccount),
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function validatePayment(array $payload = [], ?string $merchantAccount = null): array
    {
        return $this->transport->post(
            'transactions/deposits/validate-payment/',
            $payload,
            $this->merchantAccountHeaders($merchantAccount),
        );
    }
}
