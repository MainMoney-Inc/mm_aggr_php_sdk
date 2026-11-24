<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Pricing extends Resource
{
    /**
     * @param array<string, scalar|null> $query `financial_entity_code`, `operation_type`
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function get(array $query = []): array
    {
        return $this->transport->get('manage/general/pricing/', $query);
    }
}
