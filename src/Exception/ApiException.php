<?php

declare(strict_types=1);

namespace MainMoney\Aggregator\Exception;

final class ApiException extends AggregatorException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly array $errors = [],
        private readonly mixed $responseBody = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getResponseBody(): mixed
    {
        return $this->responseBody;
    }

    /**
     * @param array<string, mixed> $envelope
     */
    public static function fromEnvelope(array $envelope, int $statusCode): self
    {
        $message = isset($envelope['message']) && is_string($envelope['message'])
            ? $envelope['message']
            : 'Aggregator request failed';
        $responseData = $envelope['response_data'] ?? [];
        $errors = [];
        if (is_array($responseData) && isset($responseData['errors']) && is_array($responseData['errors'])) {
            $errors = $responseData['errors'];
        }

        return new self($message, $statusCode, $errors, $envelope);
    }
}
