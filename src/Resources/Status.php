<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Status extends Resource
{
    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function check(string $operationType, string $reference): array
    {
        return $this->transport->post(
            'transactions/status/check/'.$operationType.'/',
            ['reference' => $reference],
        );
    }
}
