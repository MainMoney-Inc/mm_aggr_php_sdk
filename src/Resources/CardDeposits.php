<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

/**
 * Card deposits. The contract lists no `X-Merchant-Account` header on this path.
 */
final class CardDeposits extends Resource
{
    /**
     * Confirm a card payment with the authenticator token (the contract does not
     * document the request body; pass what your provider flow requires).
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function validatePayment(array $payload = []): array
    {
        return $this->transport->post('transactions/card-deposits/validate-payment/', $payload);
    }
}
