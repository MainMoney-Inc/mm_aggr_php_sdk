<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Auth;

use MainMoney\Aggregator\Exception\AuthenticationException;
use MainMoney\Aggregator\Http\HttpClient;

final class TokenStore
{
    private ?AccessToken $current = null;

    public function __construct(
        private readonly HttpClient $http,
        private readonly string $baseUri,
        private readonly string $clientId,
        private readonly string $secret,
        private readonly ?int $expiresIn = null,
    ) {
    }

    public function getAccessToken(): string
    {
        if ($this->current === null || $this->current->isExpiring()) {
            $this->current = $this->exchange();
        }

        return $this->current->accessToken;
    }

    public function invalidate(): void
    {
        $this->current = null;
    }

    private function exchange(): AccessToken
    {
        $body = [
            'client_id' => $this->clientId,
            'secret' => $this->secret,
        ];
        if ($this->expiresIn !== null) {
            $body['expires_in'] = $this->expiresIn;
        }

        $url = rtrim($this->baseUri, '/').'/auth/tokens/exchange/';
        $response = $this->http->request('POST', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);

        $decoded = json_decode($response->body, true);
        if (
            $response->statusCode >= 400
            || !is_array($decoded)
            || !isset($decoded['access_token'])
            || !is_string($decoded['access_token'])
        ) {
            throw new AuthenticationException('Token exchange failed');
        }

        $expiresAtRaw = isset($decoded['expires_at']) && is_string($decoded['expires_at'])
            ? $decoded['expires_at']
            : 'now +1 hour';

        return new AccessToken(
            accessToken: $decoded['access_token'],
            tokenType: isset($decoded['token_type']) && is_string($decoded['token_type'])
                ? $decoded['token_type']
                : 'Bearer',
            expiresIn: isset($decoded['expires_in']) && is_numeric($decoded['expires_in'])
                ? (int) $decoded['expires_in']
                : 3600,
            expiresAt: new \DateTimeImmutable($expiresAtRaw),
        );
    }
}
