<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Http;

use MainMoney\Aggregator\Exception\ApiException;

final class StreamHttpClient implements HttpClient
{
    public function __construct(private readonly float $timeout = 30.0)
    {
    }

    public function request(string $method, string $uri, array $options = []): HttpResponse
    {
        $headerBag = $options['headers'] ?? [];
        if (!is_array($headerBag)) {
            $headerBag = [];
        }
        /** @var array<string, string> $headers */
        $headers = [];
        foreach ($headerBag as $name => $value) {
            if (is_string($name) && (is_string($value) || is_numeric($value))) {
                $headers[$name] = (string) $value;
            }
        }

        $query = [];
        if (isset($options['query']) && is_array($options['query'])) {
            foreach ($options['query'] as $name => $value) {
                if (is_string($name) && (is_string($value) || is_int($value) || is_float($value) || is_bool($value))) {
                    $query[$name] = $value;
                }
            }
        }
        if ($query !== []) {
            $uri .= (str_contains($uri, '?') ? '&' : '?').http_build_query($query);
        }

        $body = '';
        if (isset($options['json'])) {
            if (!is_array($options['json'])) {
                throw new ApiException('JSON body must be an object', 0);
            }
            $encoded = json_encode($options['json'], JSON_THROW_ON_ERROR);
            if (!is_string($encoded)) {
                throw new ApiException('Failed to encode JSON body', 0);
            }
            $body = $encoded;
            $headers += [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
        }

        $headerLines = '';
        foreach ($headers as $name => $value) {
            $headerLines .= $name.': '.$value."\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $headerLines,
                'content' => $body,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);

        $raw = file_get_contents($uri, false, $context);
        if ($raw === false) {
            throw new ApiException('HTTP request failed: '.$uri, 0);
        }

        /** @var list<string> $responseHeaderLines */
        $responseHeaderLines = $http_response_header ?? [];
        $statusCode = 0;
        $responseHeaders = [];
        foreach ($responseHeaderLines as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $match) === 1) {
                $statusCode = (int) $match[1];
                continue;
            }
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))][] = trim($parts[1]);
            }
        }

        return new HttpResponse($statusCode, $raw, $responseHeaders);
    }
}
