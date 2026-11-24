<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

use MainMoney\Aggregator\Http\Transport;

abstract class Resource
{
    public function __construct(protected readonly Transport $transport)
    {
    }

    /**
     * @return array<string, string>
     */
    protected function idempotencyHeaders(?string $idempotencyKey): array
    {
        if ($idempotencyKey === null || $idempotencyKey === '') {
            return [];
        }

        return ['Idempotency-Key' => $idempotencyKey];
    }

    /**
     * Headers to act on behalf of one of your sellers (X-Merchant-Account).
     *
     * @return array<string, string>
     */
    protected function merchantAccountHeaders(?string $merchantAccount): array
    {
        if ($merchantAccount === null || $merchantAccount === '') {
            return [];
        }

        return ['X-Merchant-Account' => $merchantAccount];
    }
}
