<?php

declare(strict_types=1);

namespace App\Support;

final class CheckoutSession
{
    public function __construct(
        public readonly string $token,
        public readonly string $reference,
        public readonly ?string $amount,
        public readonly ?string $currency,
        public readonly bool $lockAmount,
        public readonly string $operation,
        public readonly float $expiresAt,
        public readonly ?int $orderId = null,
        public readonly ?int $transferId = null,
    ) {
    }
}
