<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Fees extends Resource
{
    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function list(array $query = []): array
    {
        return $this->transport->get('manage/general/fees/', $query);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function simulate(array $payload): array
    {
        return $this->transport->post('manage/general/fees/simulate/', $payload);
    }
}
