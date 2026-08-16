<?php

declare(strict_types=1);

namespace MainMoney\Aggregator;

use MainMoney\Aggregator\Auth\TokenStore;
use MainMoney\Aggregator\Http\HttpClient;
use MainMoney\Aggregator\Http\StreamHttpClient;
use MainMoney\Aggregator\Http\Transport;
use MainMoney\Aggregator\Resources\AmountLimits;
use MainMoney\Aggregator\Resources\CheckoutPreferences;
use MainMoney\Aggregator\Resources\Countries;
use MainMoney\Aggregator\Resources\Customers;
use MainMoney\Aggregator\Resources\Deposits;
use MainMoney\Aggregator\Resources\Fees;
use MainMoney\Aggregator\Resources\Payouts;
use MainMoney\Aggregator\Resources\Providers;
use MainMoney\Aggregator\Resources\Refunds;
use MainMoney\Aggregator\Resources\Remittances;
use MainMoney\Aggregator\Resources\Status;
use MainMoney\Aggregator\Resources\Transactions;
use MainMoney\Aggregator\Resources\Wallets;
use MainMoney\Aggregator\Webhook\WebhookVerifier;

final class Client
{
    public const PRODUCTION_BASE_URI = 'https://aggregator.mainmoney.net/api/v1/';
    public const TEST_BASE_URI = 'https://testaggregator.mainmoney.net/api/v1/';

    public readonly Deposits $deposits;
    public readonly Payouts $payouts;
    public readonly Remittances $remittances;
    public readonly Refunds $refunds;
    public readonly Status $status;
    public readonly Customers $customers;
    public readonly Wallets $wallets;
    public readonly Transactions $transactions;
    public readonly Countries $countries;
    public readonly Providers $providers;
    public readonly Fees $fees;
    public readonly AmountLimits $amountLimits;
    public readonly CheckoutPreferences $checkoutPreferences;
    public readonly WebhookVerifier $webhooks;

    private readonly string $baseUri;

    public function __construct(
        string $clientId,
        string $secret,
        ?string $baseUri = null,
        bool $test = false,
        ?HttpClient $httpClient = null,
        float $timeout = 30.0,
        ?int $tokenExpiresIn = null,
    ) {
        $this->baseUri = self::normalizeBaseUri(
            $baseUri ?? ($test ? self::TEST_BASE_URI : self::PRODUCTION_BASE_URI),
        );
        $http = $httpClient ?? new StreamHttpClient($timeout);
        $tokens = new TokenStore($http, $this->baseUri, $clientId, $secret, $tokenExpiresIn);
        $transport = new Transport($http, $this->baseUri, $tokens);

        $this->deposits = new Deposits($transport);
        $this->payouts = new Payouts($transport);
        $this->remittances = new Remittances($transport);
        $this->refunds = new Refunds($transport);
        $this->status = new Status($transport);
        $this->customers = new Customers($transport);
        $this->wallets = new Wallets($transport);
        $this->transactions = new Transactions($transport);
        $this->countries = new Countries($transport);
        $this->providers = new Providers($transport);
        $this->fees = new Fees($transport);
        $this->amountLimits = new AmountLimits($transport);
        $this->checkoutPreferences = new CheckoutPreferences($transport);
        $this->webhooks = new WebhookVerifier();
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    private static function normalizeBaseUri(string $baseUri): string
    {
        $normalized = rtrim(trim($baseUri), '/');
        if (!str_ends_with(strtolower($normalized), '/api/v1')) {
            $normalized .= '/api/v1';
        }

        return $normalized.'/';
    }
}
