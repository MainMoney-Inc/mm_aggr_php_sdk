<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Transactions extends Resource
{
    /**
     * @param string $operationType deposit, payout, refund, remittance or settlement
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function list(string $operationType, array $query = [], ?string $merchantAccount = null): array
    {
        return $this->transport->get(
            'manage/merchant-admin/transactions/'.rawurlencode($operationType).'/',
            $query,
            $this->merchantAccountHeaders($merchantAccount),
        );
    }
}
