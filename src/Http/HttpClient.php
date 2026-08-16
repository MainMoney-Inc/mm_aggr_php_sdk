<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Http;

interface HttpClient
{
    /**
     * @param array{
     *     headers?: array<string, string>,
     *     json?: array<string, mixed>,
     *     query?: array<string, scalar>
     * } $options
     */
    public function request(string $method, string $uri, array $options = []): HttpResponse;
}
