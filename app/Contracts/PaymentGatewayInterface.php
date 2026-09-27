<?php

declare(strict_types=1);

namespace App\Contracts;

interface PaymentGatewayInterface
{
    public function createOrder(string $idempotencyKey, int $amountCents, string $licenseType): string;
    public function verifyWebhookSignature(string $payload, array $headers): bool;
    public function getOrderStatus(string $paypalOrderId): string;
}
