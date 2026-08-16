<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Tests;

use MainMoney\Aggregator\Http\HttpClient;
use MainMoney\Aggregator\Http\HttpResponse;

/**
 * Test double that returns queued responses and records requests.
 */
final class MockHttpClient implements HttpClient
{
    /**
     * @var list<HttpResponse>
     */
    private array $queue = [];

    /**
     * @var list<array{method: string, uri: string, options: array<string, mixed>}>
     */
    public array $history = [];

    public function enqueue(HttpResponse ...$responses): void
    {
        foreach ($responses as $response) {
            $this->queue[] = $response;
        }
    }

    public function request(string $method, string $uri, array $options = []): HttpResponse
    {
        $this->history[] = [
            'method' => $method,
            'uri' => $uri,
            'options' => $options,
        ];
        $next = array_shift($this->queue);
        if ($next === null) {
            throw new \RuntimeException('MockHttpClient queue is empty');
        }

        return $next;
    }
}
