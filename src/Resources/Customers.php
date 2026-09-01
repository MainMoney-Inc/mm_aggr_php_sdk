<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

final class Customers extends Resource
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function lookup(array $payload): array
    {
        return $this->transport->post('transactions/customers/lookup/', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function kyc(array $payload): array
    {
        return $this->transport->post('transactions/customers/kyc/', $payload);
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function matchProvider(string $accountNumber, bool $getLookup = false, ?string $operationType = null): array
    {
        return $this->transport->get('transactions/customers/match-provider/', [
            'account_number' => $accountNumber,
            'get_lookup' => $getLookup ? 'true' : null,
            'operation_type' => $operationType,
        ]);
    }
}
