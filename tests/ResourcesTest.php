<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Tests;

use MainMoney\Aggregator\Client;
use MainMoney\Aggregator\Http\HttpResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResourcesTest extends TestCase
{
    private const SELLER = '0d9a6a0e-0000-4000-8000-0000000000aa';

    private function token(): HttpResponse
    {
        return new HttpResponse(200, json_encode([
            'access_token' => 'tok_1',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $body
     */
    private function json(array $body, int $status = 200): HttpResponse
    {
        return new HttpResponse($status, json_encode($body, JSON_THROW_ON_ERROR));
    }

    private function client(MockHttpClient &$mock, HttpResponse ...$responses): Client
    {
        $mock = new MockHttpClient();
        $mock->enqueue($this->token(), ...$responses);

        return new Client(
            clientId: 'client-id',
            secret: 'secret',
            baseUri: 'https://example.test/api/v1/',
            httpClient: $mock,
        );
    }

    /**
     * @return array{method: string, uri: string, options: array<string, mixed>}
     */
    private function last(MockHttpClient $mock): array
    {
        return $mock->history[count($mock->history) - 1];
    }

    /**
     * @param array<string, mixed> $options
     */
    private function header(array $options, string $name): ?string
    {
        $headers = $options['headers'];
        self::assertIsArray($headers);

        return isset($headers[$name]) && is_string($headers[$name]) ? $headers[$name] : null;
    }

    public function testSellersLifecycle(): void
    {
        $mock = new MockHttpClient();
        $client = $this->client(
            $mock,
            $this->json(['id' => self::SELLER, 'kyb_status' => 'DRAFT'], 201),
            $this->json(['count' => 1, 'results' => []]),
            $this->json([['id' => 'c1', 'code' => 'CD', 'name' => 'DR Congo']]),
            $this->json(['id' => self::SELLER]),
            $this->json(['id' => self::SELLER]),
            $this->json(['status' => 'DRAFT']),
            $this->json(['status' => 'DRAFT'], 200),
            $this->json(['status' => 'DRAFT', 'capture_token' => ['token' => 't']], 201),
            $this->json(['status' => 'IN_REVIEW'], 202),
            $this->json(['url' => 'https://x', 'token' => 't', 'expires_at' => '2026-01-01T00:00:00Z'], 201),
            $this->json(['revoked' => 1]),
        );

        $created = $client->sellers->create(['sub_type' => 'LEGAL', 'name' => 'Shop', 'email' => 's@x.test', 'country' => 'c1']);
        self::assertSame(self::SELLER, $created['id']);
        self::assertSame('POST', $mock->history[1]['method']);
        self::assertStringEndsWith('/api/v1/sellers/', $mock->history[1]['uri']);

        $client->sellers->list(['page' => 2]);
        $client->sellers->countries();
        self::assertStringEndsWith('/api/v1/sellers/countries/', $this->last($mock)['uri']);
        $client->sellers->get(self::SELLER);
        $client->sellers->update(self::SELLER, ['name' => 'New']);
        self::assertSame('PATCH', $this->last($mock)['method']);
        self::assertStringEndsWith('/sellers/'.self::SELLER.'/', $this->last($mock)['uri']);
        $client->sellers->kyb(self::SELLER);
        self::assertStringEndsWith('/kyb/', $this->last($mock)['uri']);
        $client->sellers->updateKyb(self::SELLER, ['jurisdiction' => 'CD']);
        self::assertSame('PATCH', $this->last($mock)['method']);
        self::assertSame('CD', $this->last($mock)['options']['json']['jurisdiction']);
        $started = $client->sellers->startKyb(self::SELLER);
        self::assertStringEndsWith('/kyb/start/', $this->last($mock)['uri']);
        self::assertIsArray($started['capture_token']);
        $client->sellers->submitKyb(self::SELLER);
        self::assertStringEndsWith('/kyb/submit/', $this->last($mock)['uri']);
        $client->sellers->createOnboardingLink(self::SELLER);
        self::assertSame('POST', $this->last($mock)['method']);
        self::assertStringEndsWith('/onboarding-link/', $this->last($mock)['uri']);
        $revoked = $client->sellers->revokeOnboardingLink(self::SELLER);
        self::assertSame('DELETE', $this->last($mock)['method']);
        self::assertSame(1, $revoked['revoked']);
    }

    public function testTransfers(): void
    {
        $mock = new MockHttpClient();
        $transfer = ['id' => 't1', 'reference' => 'TR-1', 'direction' => 'TO_SUB_MERCHANT'];
        $client = $this->client($mock, $this->json($transfer, 201), $this->json([$transfer]), $this->json($transfer));

        $client->transfers->create([
            'reference' => 'TR-1',
            'direction' => 'TO_SUB_MERCHANT',
            'sub_merchant_id' => self::SELLER,
            'amount' => '10.00',
            'currency' => 'USD',
        ]);
        self::assertSame('POST', $mock->history[1]['method']);
        self::assertStringEndsWith('/transactions/transfers/', $mock->history[1]['uri']);
        self::assertNull($this->header($mock->history[1]['options'], 'X-Merchant-Account'));

        $client->transfers->list(['reference' => 'TR-1']);
        self::assertSame('TR-1', $this->last($mock)['options']['query']['reference']);
        $one = $client->transfers->get('t1');
        self::assertStringEndsWith('/transactions/transfers/t1/', $this->last($mock)['uri']);
        self::assertSame('t1', $one['id']);
    }

    public function testPricingAndCardEndpoints(): void
    {
        $mock = new MockHttpClient();
        $client = $this->client(
            $mock,
            $this->json(['items' => []]),
            $this->json(['status' => 'SUCCESS']),
            $this->json(['status' => 'PENDING'], 201),
        );

        $client->pricing->get(['operation_type' => 'DEPOSIT']);
        self::assertStringEndsWith('/manage/general/pricing/', $this->last($mock)['uri']);
        self::assertSame('DEPOSIT', $this->last($mock)['options']['query']['operation_type']);

        $client->cardDeposits->validatePayment(['token' => 'abc']);
        self::assertStringEndsWith('/transactions/card-deposits/validate-payment/', $this->last($mock)['uri']);

        $client->cardPayouts->createBusinessMerchantAccount(['reference' => 'P1'], 'key-1');
        self::assertStringEndsWith('/transactions/card-payouts/business/merchant-account/', $this->last($mock)['uri']);
        self::assertSame('key-1', $this->header($this->last($mock)['options'], 'Idempotency-Key'));
    }

    /**
     * @return array<string, array{callable(Client, ?string): mixed, string}>
     */
    public static function merchantAccountCalls(): array
    {
        return [
            'deposits.create' => [static fn (Client $c, ?string $m) => $c->deposits->create(['reference' => 'R'], 'k', $m), 'transactions/deposits/'],
            'deposits.validatePayment' => [static fn (Client $c, ?string $m) => $c->deposits->validatePayment([], $m), 'deposits/validate-payment/'],
            'payouts.create' => [static fn (Client $c, ?string $m) => $c->payouts->create(['reference' => 'R'], null, $m), 'transactions/payouts/'],
            'payouts.createBusiness' => [static fn (Client $c, ?string $m) => $c->payouts->createBusiness(['reference' => 'R'], null, $m), 'payouts/business/'],
            'payouts.createBusinessMerchantAccount' => [static fn (Client $c, ?string $m) => $c->payouts->createBusinessMerchantAccount(['reference' => 'R'], null, $m), 'payouts/business/merchant-account/'],
            'refunds.create' => [static fn (Client $c, ?string $m) => $c->refunds->create(['reference' => 'R'], null, $m), 'transactions/refunds/'],
            'remittances.create' => [static fn (Client $c, ?string $m) => $c->remittances->create(['reference' => 'R'], null, $m), 'transactions/remittances/'],
            'status.check' => [static fn (Client $c, ?string $m) => $c->status->check('deposit', 'R', $m), 'status/check/deposit/'],
            'wallets.list' => [static fn (Client $c, ?string $m) => $c->wallets->list([], $m), 'merchant-admin/wallets/'],
            'transactions.list' => [static fn (Client $c, ?string $m) => $c->transactions->list('payout', [], $m), 'merchant-admin/transactions/payout/'],
            'reports.summary' => [static fn (Client $c, ?string $m) => $c->reports->summary([], $m), 'reports/summary/'],
            'reports.charts' => [static fn (Client $c, ?string $m) => $c->reports->charts([], $m), 'reports/charts/'],
        ];
    }

    /**
     * @param callable(Client, ?string): mixed $call
     */
    #[DataProvider('merchantAccountCalls')]
    public function testMerchantAccountHeaderIsOptional(callable $call, string $pathSuffix): void
    {
        $mock = new MockHttpClient();
        $client = $this->client($mock, $this->json(['status' => 'PENDING']), $this->json(['status' => 'PENDING']));

        $call($client, self::SELLER);
        self::assertStringContainsString($pathSuffix, $this->last($mock)['uri']);
        self::assertSame(self::SELLER, $this->header($this->last($mock)['options'], 'X-Merchant-Account'));

        $call($client, null);
        self::assertNull($this->header($this->last($mock)['options'], 'X-Merchant-Account'));
    }
}
