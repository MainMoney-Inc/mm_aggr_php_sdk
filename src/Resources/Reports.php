<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Reports extends Resource
{
    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function summary(array $query = []): array
    {
        return $this->transport->get('manage/merchant-admin/reports/summary/', $query);
    }

    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function charts(array $query = []): array
    {
        return $this->transport->get('manage/merchant-admin/reports/charts/', $query);
    }
}
