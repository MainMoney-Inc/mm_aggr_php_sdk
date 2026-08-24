<?php

declare(strict_types=1);

namespace App\Support;

use MainMoney\Aggregator\Client;

final class AggregatorClient
{
    public static function make(): Client
    {
        $baseUri = env('MM_BASE_URI');

        return new Client(
            clientId: (string) env('MM_CLIENT_ID', ''),
            secret: (string) env('MM_API_SECRET', ''),
            baseUri: is_string($baseUri) && $baseUri !== '' ? $baseUri : null,
            test: filter_var(env('MM_TEST', true), FILTER_VALIDATE_BOOLEAN),
        );
    }
}
