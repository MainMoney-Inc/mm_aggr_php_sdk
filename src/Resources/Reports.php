<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Reports extends Resource
{
    /**
     * @param array<string, scalar|null> $query currency, date_from, date_to, operation_type, provider_code
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function summary(array $query = [], ?string $merchantAccount = null): array
    {
        return $this->transport->get(
            'manage/merchant-admin/reports/summary/',
            $query,
            $this->merchantAccountHeaders($merchantAccount),
        );
    }

    /**
     * @param array<string, scalar|null> $query currency, date_from, date_to, operation_type, provider_code
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function charts(array $query = [], ?string $merchantAccount = null): array
    {
        return $this->transport->get(
            'manage/merchant-admin/reports/charts/',
            $query,
            $this->merchantAccountHeaders($merchantAccount),
        );
    }
}
