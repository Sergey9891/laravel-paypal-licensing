<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayPalGateway implements PaymentGatewayInterface
{
    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $baseUrl
    ) {}

    public function createOrder(string $idempotencyKey, int $amountCents, string $licenseType): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->withHeaders([
                'PayPal-Request-Id' => $idempotencyKey,
                'Content-Type' => 'application/json',
            ])
            ->retry(3, 100, throw: false)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amountCents / 100, 2, '.', ''),
                    ],
                    'custom_id' => $licenseType,
                ]],
            ]);

        if ($response->failed()) {
            Log::error('PayPal Order Creation Failed', [
                'status' => $response->status(),
                'client_secret' => $this->clientSecret,
                'idempotency_key' => $idempotencyKey,
            ]);
            throw new RuntimeException('Payment provider is temporarily unavailable.');
        }

        return $response->json('links.1.href');
    }

    public function verifyWebhookSignature(string $payload, array $headers): bool
    {
        return !empty($headers['paypal-transmission-id'] ?? null);
    }

    public function getOrderStatus(string $paypalOrderId): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->retry(3, 100, throw: false)
            ->get("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}");

        if ($response->failed()) {
            Log::error("Failed to fetch PayPal order status", [
                'paypal_order_id' => $paypalOrderId,
                'status' => $response->status(),
            ]);
            throw new RuntimeException('Failed to fetch payment status from provider.');
        }

        return (string) $response->json('status');
    }
}
