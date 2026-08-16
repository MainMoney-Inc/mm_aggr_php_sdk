<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Http;

use MainMoney\Aggregator\Auth\TokenStore;
use MainMoney\Aggregator\Exception\ApiException;
use MainMoney\Aggregator\Exception\AuthenticationException;

final class Transport
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $baseUri,
        private readonly TokenStore $tokens,
    ) {
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function post(string $path, array $body = [], array $headers = []): array
    {
        return $this->request('POST', $path, $body, [], $headers);
    }

    /**
     * @param array<string, scalar|null> $query
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>|list<mixed>
     */
    public function get(string $path, array $query = [], array $headers = []): array
    {
        return $this->request('GET', $path, null, $query, $headers);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, scalar|null> $query
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>|list<mixed>
     */
    private function request(
        string $method,
        string $path,
        ?array $body,
        array $query,
        array $headers,
        bool $retried = false,
    ): array {
        $headers['Authorization'] = 'Bearer '.$this->tokens->getAccessToken();

        try {
            $response = $this->send($method, $path, $body, $query, $headers);
        } catch (AuthenticationException $exception) {
            if ($retried) {
                throw $exception;
            }
            $this->tokens->invalidate();
            unset($headers['Authorization']);

            return $this->request($method, $path, $body, $query, $headers, true);
        }

        return $this->decode($response);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, scalar|null> $query
     * @param array<string, string> $headers
     */
    private function send(
        string $method,
        string $path,
        ?array $body,
        array $query,
        array $headers,
    ): HttpResponse {
        $options = ['headers' => $headers];
        if ($body !== null) {
            $options['json'] = $body;
        }
        $filteredQuery = array_filter(
            $query,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
        if ($filteredQuery !== []) {
            $options['query'] = $filteredQuery;
        }

        $response = $this->http->request($method, $this->url($path), $options);
        if ($response->statusCode === 401) {
            throw new AuthenticationException('Authentication failed');
        }

        return $response;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    private function decode(HttpResponse $response): array
    {
        $status = $response->statusCode;
        $raw = $response->body;
        $decoded = $raw === '' ? [] : json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        if ($status >= 400) {
            if (isset($decoded['success']) && $decoded['success'] === false) {
                throw ApiException::fromEnvelope($decoded, $status);
            }
            $detail = $decoded['detail'] ?? $decoded['message'] ?? $raw;
            $message = is_string($detail) ? $detail : 'Aggregator request failed';

            throw new ApiException($message, $status, is_array($decoded) ? $decoded : [], $decoded);
        }

        if (array_key_exists('success', $decoded)) {
            if ($decoded['success'] === false) {
                throw ApiException::fromEnvelope($decoded, $status);
            }
            $data = $decoded['response_data'] ?? [];

            return is_array($data) ? $data : [];
        }

        return $decoded;
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUri, '/').'/'.ltrim($path, '/');
    }
}
