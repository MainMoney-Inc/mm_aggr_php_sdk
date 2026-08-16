<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Wallets extends Resource
{
    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function list(array $query = []): array
    {
        return $this->transport->get('manage/merchant-admin/wallets/', $query);
    }
}
