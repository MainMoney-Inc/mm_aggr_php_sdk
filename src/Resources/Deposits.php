<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Deposits extends Resource
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function create(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->transport->post('transactions/deposits/', $payload, $this->idempotencyHeaders($idempotencyKey));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function validatePayment(array $payload = []): array
    {
        return $this->transport->post('transactions/deposits/validate-payment/', $payload);
    }
}
