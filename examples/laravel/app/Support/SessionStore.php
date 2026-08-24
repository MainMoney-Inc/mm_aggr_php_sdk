<?php

declare(strict_types=1);

namespace App\Support;

final class SessionStore
{
    private const TTL = 1800;

    /** @var array<string, CheckoutSession> */
    private static array $sessions = [];

    public static function create(
        string $reference,
        ?string $amount,
        ?string $currency,
        bool $lockAmount,
        string $operation,
        ?int $orderId = null,
        ?int $transferId = null,
    ): CheckoutSession {
        $token = bin2hex(random_bytes(18));
        $session = new CheckoutSession(
            $token,
            $reference,
            $amount,
            $currency,
            $lockAmount,
            $operation,
            microtime(true) + self::TTL,
            $orderId,
            $transferId,
        );
        self::$sessions[$token] = $session;

        return $session;
    }

    public static function get(string $token): ?CheckoutSession
    {
        $session = self::$sessions[$token] ?? null;
        if ($session === null || $session->expiresAt < microtime(true)) {
            unset(self::$sessions[$token]);

            return null;
        }

        return $session;
    }
}
