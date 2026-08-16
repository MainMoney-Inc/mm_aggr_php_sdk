<?php

declare(strict_types=1);

namespace MainMoney\Aggregator;

final class Client
{
    public function __construct(
        private readonly string $baseUri,
        private readonly string $apiKey,
    ) {
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }
}
