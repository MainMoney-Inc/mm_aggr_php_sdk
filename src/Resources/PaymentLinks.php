<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

/**
 * Hosted payment links. Requires an API key with the PAYMENT_LINK scope.
 *
 * Every method accepts an optional `$merchantAccount` (a seller's account id)
 * which is sent as `X-Merchant-Account` to act on behalf of that seller.
 */
final class PaymentLinks extends Resource
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function create(array $payload, ?string $idempotencyKey = null, ?string $merchantAccount = null): array
    {
        return $this->transport->post(
            'payment-links/',
            $payload,
            $this->idempotencyHeaders($idempotencyKey) + $this->merchantAccountHeaders($merchantAccount),
        );
    }

    /**
     * @param array<string, scalar|null> $query e.g. `status`, `page`, `page_size`, `ordering`, `search`
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function list(array $query = [], ?string $merchantAccount = null): array
    {
        return $this->transport->get('payment-links/', $query, $this->merchantAccountHeaders($merchantAccount));
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function get(string $id, ?string $merchantAccount = null): array
    {
        return $this->transport->get(
            'payment-links/'.rawurlencode($id).'/',
            [],
            $this->merchantAccountHeaders($merchantAccount),
        );
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    public function cancel(string $id, ?string $merchantAccount = null): array
    {
        return $this->transport->post(
            'payment-links/'.rawurlencode($id).'/cancel/',
            [],
            $this->merchantAccountHeaders($merchantAccount),
        );
    }
}
