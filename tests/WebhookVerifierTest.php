<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Tests;

use MainMoney\Aggregator\Exception\WebhookSignatureException;
use MainMoney\Aggregator\Webhook\WebhookVerifier;
use PHPUnit\Framework\TestCase;

final class WebhookVerifierTest extends TestCase
{
    public function testAcceptsPythonCanonicalJsonHmac(): void
    {
        // json.dumps(payload, separators=(",", ":"), sort_keys=True)
        $rawBody = '{"amount":"100.00","currency":"KES","merchant_reference":"ORDER-123","type":"DEPOSIT"}';
        $secret = 'whsec_test';
        $signature = hash_hmac('sha256', $rawBody, $secret);

        $verifier = new WebhookVerifier();
        self::assertTrue($verifier->verify($rawBody, $signature, $secret));
    }

    public function testRejectsTamperedBody(): void
    {
        $rawBody = '{"amount":"100.00","currency":"KES"}';
        $secret = 'whsec_test';
        $signature = hash_hmac('sha256', $rawBody, $secret);

        $verifier = new WebhookVerifier();
        self::assertFalse($verifier->verify('{"amount":"999.00","currency":"KES"}', $signature, $secret));
    }

    public function testVerifyOrFailThrows(): void
    {
        $this->expectException(WebhookSignatureException::class);
        (new WebhookVerifier())->verifyOrFail('{}', 'deadbeef', 'secret');
    }
}
