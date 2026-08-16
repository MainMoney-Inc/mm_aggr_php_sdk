<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Webhook;

use MainMoney\Aggregator\Exception\WebhookSignatureException;

final class WebhookVerifier
{
    public function verify(string $rawBody, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, strtolower($signature));
    }

    public function verifyOrFail(string $rawBody, string $signature, string $secret): void
    {
        if (!$this->verify($rawBody, $signature, $secret)) {
            throw new WebhookSignatureException('Invalid X-Webhook-Signature');
        }
    }
}
