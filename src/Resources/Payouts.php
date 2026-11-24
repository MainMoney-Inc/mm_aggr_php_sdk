<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

/**
 * Payouts. Every create method accepts an optional `$merchantAccount` (a seller
 * id) sent as `X-Merchant-Account`.
 */
final class Payouts extends Resource
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
            'transactions/payouts/',
            $payload,
            $this->idempotencyHeaders($idempotencyKey) + $this->merchantAccountHeaders($merchantAccount),
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function createBusiness(
        array $payload,
        ?string $idempotencyKey = null,
        ?string $merchantAccount = null,
    ): array {
        return $this->transport->post(
            'transactions/payouts/business/',
            $payload,
            $this->idempotencyHeaders($idempotencyKey) + $this->merchantAccountHeaders($merchantAccount),
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function createBusinessMerchantAccount(
        array $payload,
        ?string $idempotencyKey = null,
        ?string $merchantAccount = null,
    ): array {
        return $this->transport->post(
            'transactions/payouts/business/merchant-account/',
            $payload,
            $this->idempotencyHeaders($idempotencyKey) + $this->merchantAccountHeaders($merchantAccount),
        );
    }
}
