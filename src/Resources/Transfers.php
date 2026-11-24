<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

/**
 * MainMoney Rails: move funds between the platform wallet and a seller's.
 */
final class Transfers extends Resource
{
    /**
     * @param array<string, mixed> $payload reference, direction (TO_SUB_MERCHANT|FROM_SUB_MERCHANT),
     *                                      sub_merchant_id, amount, currency, description (optional)
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function create(array $payload): array
    {
        return $this->transport->post('transactions/transfers/', $payload);
    }

    /**
     * @param array<string, scalar|null> $query `reference`, `sub_merchant_id`
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function list(array $query = []): array
    {
        return $this->transport->get('transactions/transfers/', $query);
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function get(string $id): array
    {
        return $this->transport->get('transactions/transfers/'.rawurlencode($id).'/');
    }
}
