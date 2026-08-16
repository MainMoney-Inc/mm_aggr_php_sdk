<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Auth;

final class AccessToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly \DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpiring(int $skewSeconds = 60): bool
    {
        $threshold = $this->expiresAt->getTimestamp() - $skewSeconds;

        return time() >= $threshold;
    }
}
