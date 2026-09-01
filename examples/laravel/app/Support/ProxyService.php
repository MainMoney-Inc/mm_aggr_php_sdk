<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Order;
use App\Models\Transfer;
use MainMoney\Aggregator\Exception\AggregatorException;
use MainMoney\Aggregator\Exception\ApiException;
use Throwable;

final class ProxyService
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     *
     * @return array{status: int, body: mixed}
     */
    public function handle(string $method, string $route, array $query, array $body, CheckoutSession $session): array
    {
        try {
            return ['status' => 200, 'body' => $this->dispatch(strtoupper($method), trim($route, '/'), $query, $body, $session)];
        } catch (ApiException $exception) {
            $status = $exception->getStatusCode() >= 400 ? $exception->getStatusCode() : 400;

            return [
                'status' => $status,
                'body' => ['message' => $exception->getMessage(), 'errors' => $exception->getErrors()],
            ];
        } catch (AggregatorException $exception) {
            return ['status' => 400, 'body' => ['message' => $exception->getMessage()]];
        } catch (Throwable $exception) {
            return ['status' => 400, 'body' => ['message' => $exception->getMessage()]];
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    private function dispatch(string $method, string $route, array $query, array $body, CheckoutSession $session): mixed
    {
        $client = AggregatorClient::make();
        if ($method === 'GET' && $route === 'countries') {
            return $client->countries->list();
        }
        if ($method === 'GET' && $route === 'providers') {
            return $client->providers->list($this->scalarQuery($query));
        }
        if ($method === 'GET' && $route === 'match-provider') {
            $account = $this->stringQuery($query, 'account_number');
            $lookup = in_array((string) ($query['get_lookup'] ?? ''), ['1', 'true', 'yes'], true);
            $operationType = $this->stringQuery($query, 'operation_type');

            return $client->customers->matchProvider(
                $account,
                $lookup,
                $operationType !== '' ? $operationType : null,
            );
        }
        if ($method === 'GET' && $route === 'amount-limits') {
            return $client->amountLimits->list($this->scalarQuery($query));
        }
        if ($method === 'POST' && $route === 'fees/simulate') {
            return $client->fees->simulate($body);
        }
        if ($method === 'GET' && $route === 'checkout-preferences') {
            return $client->checkoutPreferences->get();
        }
        if ($method === 'POST' && $route === 'deposits') {
            $payload = $body;
            $payload['reference'] = $session->reference;
            if ($session->lockAmount && $session->amount !== null) {
                $payload['amount'] = $session->amount;
            }
            $result = $client->deposits->create($payload, $session->reference);
            $this->markOrder($session, 'pending');

            return $result;
        }
        if ($method === 'POST' && $route === 'payouts') {
            $payload = $body;
            $payload['reference'] = $session->reference;
            if ($session->lockAmount && $session->amount !== null) {
                $payload['amount'] = $session->amount;
            }
            $destination = (string) ($payload['customer_phone'] ?? $payload['destination_account'] ?? '');
            $result = $client->payouts->create($payload, $session->reference);
            $this->markTransfer($session, 'pending', $destination);

            return $result;
        }
        if ($method === 'GET' && $route === 'status') {
            $reference = $this->stringQuery($query, 'reference') ?: $session->reference;
            $operation = $this->stringQuery($query, 'operation') ?: $session->operation;
            $result = $client->status->check($operation, $reference);
            $status = $this->extractStatus($result);
            if ($operation === 'payout') {
                $this->markTransfer($session, $status);
            } else {
                $this->markOrder($session, $status);
            }

            return $result;
        }

        throw new AggregatorException('Unknown merchant backend path');
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, scalar|null>
     */
    private function scalarQuery(array $query): array
    {
        $filtered = [];
        foreach ($query as $name => $value) {
            if (is_scalar($value) || $value === null) {
                $filtered[$name] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function stringQuery(array $query, string $name): string
    {
        $value = $query[$name] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function extractStatus(mixed $result): string
    {
        if (is_array($result)) {
            $raw = $result['status'] ?? $result['transaction_status'] ?? '';
            if (is_string($raw) && $raw !== '') {
                return strtolower($raw);
            }
        }

        return 'pending';
    }

    private function mapStatus(string $status): string
    {
        $lowered = strtolower($status);
        if (in_array($lowered, ['success', 'successful', 'paid', 'completed'], true)) {
            return 'paid';
        }
        if (in_array($lowered, ['failed', 'error', 'cancelled', 'canceled'], true)) {
            return 'failed';
        }
        if ($lowered === 'refunded') {
            return 'refunded';
        }

        return 'pending';
    }

    private function markOrder(CheckoutSession $session, string $status): void
    {
        if ($session->orderId === null) {
            return;
        }
        Order::query()->whereKey($session->orderId)->update(['status' => $this->mapStatus($status)]);
    }

    private function markTransfer(CheckoutSession $session, string $status, string $destination = ''): void
    {
        if ($session->transferId === null) {
            return;
        }
        $updates = ['status' => $this->mapStatus($status)];
        if ($destination !== '') {
            $updates['destination'] = $destination;
        }
        Transfer::query()->whereKey($session->transferId)->update($updates);
    }
}
