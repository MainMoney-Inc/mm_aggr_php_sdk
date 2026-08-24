<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Transfer;
use App\Support\AggregatorClient;
use App\Support\ProxyService;
use App\Support\SessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function products(): JsonResponse
    {
        return response()->json(Product::query()->orderBy('id')->get());
    }

    public function product(int $id): JsonResponse
    {
        $product = Product::query()->find($id);
        if ($product === null) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function session(Request $request): JsonResponse
    {
        $operation = (string) $request->input('operation', 'deposit');
        $productId = $request->input('product_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        $lockAmount = true;
        $orderId = null;
        $transferId = null;
        $reference = 'EX-'.strtoupper(bin2hex(random_bytes(6)));

        if ($productId !== null) {
            $product = Product::query()->find((int) $productId);
            if ($product === null) {
                return response()->json(['message' => 'Product not found'], 404);
            }
            $amount = $product->price;
            $currency = $product->currency;
            $order = Order::query()->create([
                'reference' => $reference,
                'product_id' => $product->id,
                'amount' => $product->price,
                'currency' => $product->currency,
                'status' => 'pending',
            ]);
            $orderId = $order->id;
            $operation = 'deposit';
        } elseif ($operation === 'payout') {
            if ($amount === null || $currency === null) {
                return response()->json(['message' => 'amount and currency are required for payouts'], 400);
            }
            $transfer = Transfer::query()->create([
                'reference' => $reference,
                'amount' => (string) $amount,
                'currency' => (string) $currency,
                'status' => 'pending',
            ]);
            $transferId = $transfer->id;
        } elseif ($amount && $currency) {
            $lockAmount = (bool) $request->input('lockAmount', true);
        } else {
            return response()->json(['message' => 'product_id or amount and currency are required'], 400);
        }

        $session = SessionStore::create(
            $reference,
            $amount !== null ? (string) $amount : null,
            $currency !== null ? (string) $currency : null,
            $lockAmount,
            $operation,
            $orderId,
            $transferId,
        );
        $base = rtrim($request->getSchemeAndHttpHost(), '/');

        return response()->json([
            'merchantBackendUrl' => $base.'/payments',
            'clientToken' => $session->token,
            'pollUrl' => $base.'/payments/status',
            'pollHeaders' => ['Authorization' => 'Bearer '.$session->token],
            'locale' => 'en',
            'amount' => $session->amount,
            'currency' => $session->currency,
            'lockAmount' => $session->lockAmount,
            'reference' => $session->reference,
            'operation' => $session->operation,
        ]);
    }

    public function orders(): JsonResponse
    {
        return response()->json(Order::query()->orderByDesc('id')->get());
    }

    public function refund(int $id): JsonResponse
    {
        $order = Order::query()->find($id);
        if ($order === null) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        if ($order->status !== 'paid') {
            return response()->json(['message' => 'Only paid orders can be refunded'], 400);
        }
        $refundReference = 'RF-'.strtoupper(bin2hex(random_bytes(6)));
        $result = AggregatorClient::make()->refunds->create([
            'reference' => $refundReference,
            'original_transaction_id' => $order->reference,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'reason' => 'Example shop refund',
        ], $refundReference);
        $order->status = 'refunded';
        $order->save();

        return response()->json(['order' => $order, 'refund' => $result]);
    }

    public function transfers(): JsonResponse
    {
        return response()->json(Transfer::query()->orderByDesc('id')->get());
    }

    public function createTransfer(Request $request): JsonResponse
    {
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        $destination = (string) ($request->input('destination') ?? $request->input('customer_phone') ?? '');
        if ($amount === null || $currency === null) {
            return response()->json(['message' => 'amount and currency are required'], 400);
        }
        $reference = 'PO-'.strtoupper(bin2hex(random_bytes(6)));
        $result = AggregatorClient::make()->payouts->create([
            'provider_code' => $request->input('provider_code'),
            'reference' => $reference,
            'amount' => (string) $amount,
            'currency' => (string) $currency,
            'customer_phone' => $destination,
        ], $reference);
        $transfer = Transfer::query()->create([
            'reference' => $reference,
            'amount' => (string) $amount,
            'currency' => (string) $currency,
            'destination' => $destination,
            'status' => 'pending',
        ]);

        return response()->json(['transfer' => $transfer, 'payout' => $result], 201);
    }

    public function payments(Request $request, string $route = ''): JsonResponse
    {
        $header = (string) $request->header('Authorization', '');
        $token = str_starts_with(strtolower($header), 'bearer ') ? trim(substr($header, 7)) : '';
        $session = SessionStore::get($token);
        if ($session === null) {
            return response()->json(['message' => 'Invalid checkout session'], 401);
        }
        $result = (new ProxyService())->handle(
            $request->method(),
            $route,
            $request->query->all(),
            $request->json()->all() ?: [],
            $session,
        );

        return response()->json($result['body'], $result['status']);
    }

    public function webhooks(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        $signature = (string) $request->header('X-Webhook-Signature', '');
        $client = AggregatorClient::make();
        if (!$client->webhooks->verify($raw, $signature, (string) env('MM_WEBHOOK_SECRET', ''))) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }
        $payload = json_decode($raw, true);
        if (is_array($payload)) {
            $reference = (string) ($payload['reference'] ?? $payload['merchant_reference'] ?? '');
            $status = strtolower((string) ($payload['status'] ?? 'pending'));
            $mapped = in_array($status, ['success', 'successful', 'paid', 'completed'], true)
                ? 'paid'
                : (in_array($status, ['failed', 'error'], true) ? 'failed' : $status);
            if ($reference !== '') {
                Order::query()->where('reference', $reference)->update(['status' => $mapped]);
                Transfer::query()->where('reference', $reference)->update(['status' => $mapped]);
            }
        }

        return response()->json(['ok' => true]);
    }
}
