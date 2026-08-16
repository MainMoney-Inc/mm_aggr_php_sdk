<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Remittances extends Resource
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function create(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->transport->post(
            'transactions/remittances/',
            $payload,
            $this->idempotencyHeaders($idempotencyKey),
        );
    }
}
