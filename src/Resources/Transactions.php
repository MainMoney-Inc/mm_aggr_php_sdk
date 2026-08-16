<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Transactions extends Resource
{
    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function list(string $operationType, array $query = []): array
    {
        return $this->transport->get('manage/merchant-admin/transactions/'.$operationType.'/', $query);
    }
}
