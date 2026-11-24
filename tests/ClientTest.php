<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Tests;

use MainMoney\Aggregator\Client;
use MainMoney\Aggregator\Exception\ApiException;
use MainMoney\Aggregator\Exception\AuthenticationException;
use MainMoney\Aggregator\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    /**
     * @param list<HttpResponse> $responses
     */
    private function clientWithMock(array $responses, MockHttpClient &$mock): Client
    {
        $mock = new MockHttpClient();
        $mock->enqueue(...$responses);

        return new Client(
            baseUri: 'https://example.test/api/v1/',
            clientId: 'client-id',
            secret: 'secret',
            httpClient: $mock,
        );
    }

    private function tokenResponse(string $token = 'tok_1'): HttpResponse
    {
        $expiresAt = (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM);

        return new HttpResponse(200, json_encode([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => $expiresAt,
        ], JSON_THROW_ON_ERROR));
    }

    public function testTokenExchangeThenBearerOnFollowUp(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse(),
                new HttpResponse(200, json_encode([
                    'success' => true,
                    'response_code' => 202,
                    'response_data' => ['status' => 'PENDING', 'merchant_reference' => 'ORDER-1'],
                    'message' => 'ok',
                ], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        $result = $client->deposits->create([
            'provider_code' => 'VODACOM_MPESA_COD',
            'reference' => 'ORDER-1',
            'amount' => '100.00',
            'currency' => 'USD',
            'customer_phone' => '243820000000',
        ]);

        self::assertSame('PENDING', $result['status']);
        self::assertCount(2, $mock->history);

        $exchange = $mock->history[0];
        self::assertSame('POST', $exchange['method']);
        self::assertStringContainsString('/auth/tokens/exchange/', $exchange['uri']);
        self::assertArrayNotHasKey('Authorization', $exchange['options']['headers'] ?? []);
        self::assertSame('client-id', $exchange['options']['json']['client_id']);
        self::assertSame('secret', $exchange['options']['json']['secret']);

        $deposit = $mock->history[1];
        self::assertSame('Bearer tok_1', $deposit['options']['headers']['Authorization']);
        self::assertArrayNotHasKey('X-API-KEY', $deposit['options']['headers']);
        self::assertArrayNotHasKey('Idempotency-Key', $deposit['options']['headers']);
        self::assertSame('ORDER-1', $deposit['options']['json']['reference']);
        self::assertSame('100.00', $deposit['options']['json']['amount']);
        self::assertSame('USD', $deposit['options']['json']['currency']);
        self::assertSame('VODACOM_MPESA_COD', $deposit['options']['json']['provider_code']);
        self::assertSame('243820000000', $deposit['options']['json']['customer_phone']);
    }

    public function testTokenIsCachedAcrossCalls(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse(),
                new HttpResponse(200, json_encode(['count' => 1, 'next' => null, 'previous' => null, 'results' => []], JSON_THROW_ON_ERROR)),
                new HttpResponse(200, json_encode(['count' => 0, 'next' => null, 'previous' => null, 'results' => []], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        $client->countries->list();
        $client->wallets->list();

        self::assertCount(3, $mock->history);
        self::assertStringContainsString('/auth/tokens/exchange/', $mock->history[0]['uri']);
        self::assertStringContainsString('/manage/general/countries/', $mock->history[1]['uri']);
        self::assertStringContainsString('/manage/merchant-admin/wallets/', $mock->history[2]['uri']);
    }

    public function testUnauthorizedRetriesOnceAfterReexchange(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse('tok_old'),
                new HttpResponse(401, json_encode(['detail' => 'Token expired'], JSON_THROW_ON_ERROR)),
                $this->tokenResponse('tok_new'),
                new HttpResponse(200, json_encode([
                    'success' => true,
                    'response_data' => ['status' => 'SUCCESS'],
                    'message' => 'ok',
                ], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        $result = $client->status->check('deposit', 'ORDER-1');
        self::assertSame('SUCCESS', $result['status']);
        self::assertCount(4, $mock->history);
        self::assertSame('Bearer tok_new', $mock->history[3]['options']['headers']['Authorization']);
    }

    public function testSecondUnauthorizedFails(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse('tok_old'),
                new HttpResponse(401, '{}'),
                $this->tokenResponse('tok_new'),
                new HttpResponse(401, '{}'),
            ],
            $mock,
        );

        $this->expectException(AuthenticationException::class);
        $client->countries->list();
    }

    public function testPaginatedListIsNotUnwrapped(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse(),
                new HttpResponse(200, json_encode([
                    'count' => 1,
                    'next' => null,
                    'previous' => null,
                    'results' => [['code' => 'KE']],
                ], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        $page = $client->countries->list();
        self::assertSame(1, $page['count']);
        self::assertSame([['code' => 'KE']], $page['results']);
    }

    public function testIdempotencyKeySentOnlyWhenProvided(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse(),
                new HttpResponse(200, json_encode([
                    'success' => true,
                    'response_data' => ['status' => 'PENDING'],
                    'message' => 'ok',
                ], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        $client->payouts->create(
            [
                'provider_code' => 'MPESA_KE',
                'reference' => 'PAY-1',
                'amount' => '50.00',
                'currency' => 'KES',
                'destination_account' => '254700000000',
            ],
            idempotencyKey: 'PAY-1',
        );

        $payout = $mock->history[1];
        self::assertSame('PAY-1', $payout['options']['headers']['Idempotency-Key']);
        self::assertArrayNotHasKey('X-API-KEY', $payout['options']['headers']);
    }

    public function testEnvelopeErrorSurfacesMessage(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse(),
                new HttpResponse(400, json_encode([
                    'success' => false,
                    'response_code' => 400,
                    'response_data' => ['errors' => ['reference' => ['already exists']]],
                    'message' => 'Duplicate reference',
                ], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        try {
            $client->deposits->create([
                'provider_code' => 'MPESA_KE',
                'reference' => 'DUP',
                'amount' => '1.00',
                'currency' => 'KES',
                'customer_phone' => '+254700000000',
            ]);
            self::fail('Expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame('Duplicate reference', $exception->getMessage());
            self::assertSame(400, $exception->getStatusCode());
            self::assertSame(['reference' => ['already exists']], $exception->getErrors());
        }
    }

    public function testDefaultBaseUriIsProduction(): void
    {
        $client = new Client(clientId: 'client-id', secret: 'secret', httpClient: new MockHttpClient());
        self::assertSame(Client::PRODUCTION_BASE_URI, $client->getBaseUri());
    }

    public function testTestFlagUsesTestAggregator(): void
    {
        $client = new Client(
            clientId: 'client-id',
            secret: 'secret',
            test: true,
            httpClient: new MockHttpClient(),
        );
        self::assertSame(Client::TEST_BASE_URI, $client->getBaseUri());
    }

    public function testCustomHostWithoutApiPrefixIsNormalized(): void
    {
        $client = new Client(
            clientId: 'client-id',
            secret: 'secret',
            baseUri: 'https://aggregator.mainmoney.net',
            httpClient: new MockHttpClient(),
        );
        self::assertSame(Client::PRODUCTION_BASE_URI, $client->getBaseUri());
    }

    public function testCheckoutPreferencesGet(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse(),
                new HttpResponse(200, json_encode([
                    'success' => true,
                    'response_data' => [
                        'primary_color' => '#ff3366',
                        'secondary_color' => '#5f5e5e',
                        'accent_color' => '#b90040',
                        'background_color' => '#f8f9fb',
                        'locale' => 'en',
                        'logo' => null,
                    ],
                    'message' => 'ok',
                ], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        $prefs = $client->checkoutPreferences->get();
        self::assertSame('#ff3366', $prefs['primary_color']);
        self::assertSame('en', $prefs['locale']);
        self::assertStringContainsString('/manage/general/checkout-preferences/', $mock->history[1]['uri']);
    }

    private function linkJson(string $status = 'ACTIVE'): HttpResponse
    {
        return new HttpResponse(200, json_encode([
            'id' => '5b1c1d52-0000-4000-8000-000000000001',
            'reference' => 'INV-1',
            'amount' => '25.00',
            'currency' => 'USD',
            'status' => $status,
            'url' => 'https://pay.example.test/l/abc',
            'account_id' => null,
        ], JSON_THROW_ON_ERROR));
    }

    public function testPaymentLinksCreate(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock([$this->tokenResponse(), $this->linkJson()], $mock);

        $link = $client->paymentLinks->create(
            ['reference' => 'INV-1', 'amount' => '25.00', 'currency' => 'USD', 'single_use' => true],
            idempotencyKey: 'sess-1',
        );

        self::assertSame('https://pay.example.test/l/abc', $link['url']);
        $request = $mock->history[1];
        self::assertSame('POST', $request['method']);
        self::assertStringEndsWith('/api/v1/payment-links/', $request['uri']);
        self::assertSame('INV-1', $request['options']['json']['reference']);
        self::assertSame('sess-1', $request['options']['headers']['Idempotency-Key']);
        self::assertArrayNotHasKey('X-Merchant-Account', $request['options']['headers']);
    }

    public function testPaymentLinksListPassesStatusFilter(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse(),
                new HttpResponse(200, json_encode([
                    'count' => 1,
                    'next' => null,
                    'previous' => null,
                    'results' => [['reference' => 'INV-1', 'status' => 'ACTIVE']],
                ], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        $page = $client->paymentLinks->list(['status' => 'ACTIVE']);

        self::assertSame(1, $page['count']);
        self::assertSame('GET', $mock->history[1]['method']);
        self::assertStringEndsWith('/api/v1/payment-links/', $mock->history[1]['uri']);
        self::assertSame(['status' => 'ACTIVE'], $mock->history[1]['options']['query']);
    }

    public function testPaymentLinksGetAndCancel(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [$this->tokenResponse(), $this->linkJson(), $this->linkJson('CANCELLED')],
            $mock,
        );

        $id = '5b1c1d52-0000-4000-8000-000000000001';
        self::assertSame('ACTIVE', $client->paymentLinks->get($id)['status']);
        self::assertSame('CANCELLED', $client->paymentLinks->cancel($id)['status']);

        self::assertSame('GET', $mock->history[1]['method']);
        self::assertStringEndsWith('/api/v1/payment-links/'.$id.'/', $mock->history[1]['uri']);
        self::assertSame('POST', $mock->history[2]['method']);
        self::assertStringEndsWith('/api/v1/payment-links/'.$id.'/cancel/', $mock->history[2]['uri']);
    }

    public function testPaymentLinksSendMerchantAccountHeaderOnBehalfOfSeller(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [$this->tokenResponse(), $this->linkJson(), $this->linkJson(), $this->linkJson('CANCELLED')],
            $mock,
        );
        $seller = '0d9a6a0e-0000-4000-8000-0000000000aa';

        $client->paymentLinks->create(
            ['reference' => 'INV-1', 'amount' => '25.00', 'currency' => 'USD'],
            merchantAccount: $seller,
        );
        $client->paymentLinks->get('abc', merchantAccount: $seller);
        $client->paymentLinks->cancel('abc', merchantAccount: $seller);

        foreach ([1, 2, 3] as $i) {
            self::assertSame($seller, $mock->history[$i]['options']['headers']['X-Merchant-Account']);
        }
    }

    public function testPaymentLinksConflictSurfacesAsApiException(): void
    {
        $mock = new MockHttpClient();
        $client = $this->clientWithMock(
            [
                $this->tokenResponse(),
                new HttpResponse(409, json_encode(['detail' => 'Link already paid'], JSON_THROW_ON_ERROR)),
            ],
            $mock,
        );

        try {
            $client->paymentLinks->cancel('abc');
            self::fail('Expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame('Link already paid', $exception->getMessage());
            self::assertSame(409, $exception->getStatusCode());
        }
    }
}
