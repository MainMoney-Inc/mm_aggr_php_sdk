<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Resources;

/**
 * Card payouts. The contract lists no `X-Merchant-Account` header on this path.
 */
final class CardPayouts extends Resource
{
    /**
     * Business payout to a provider merchant account (shortcode).
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function createBusinessMerchantAccount(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->transport->post(
            'transactions/card-payouts/business/merchant-account/',
            $payload,
            $this->idempotencyHeaders($idempotencyKey),
        );
    }
}
