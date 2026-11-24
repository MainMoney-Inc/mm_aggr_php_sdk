<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Status extends Resource
{
    /**
     * @param string $operationType deposit, payout, refund or remittance
     * @param string $reference merchant_reference or internal_reference
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function check(string $operationType, string $reference, ?string $merchantAccount = null): array
    {
        return $this->transport->post(
            'transactions/status/check/'.rawurlencode($operationType).'/',
            ['reference' => $reference],
            $this->merchantAccountHeaders($merchantAccount),
        );
    }
}
