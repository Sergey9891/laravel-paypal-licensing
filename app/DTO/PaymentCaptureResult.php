<?php

declare(strict_types=1);

namespace App\DTO;

readonly class PaymentCaptureResult
{
    public function __construct(
        public string $paypalOrderId,
        public int $amountCents,
        public string $licenseType,
        public string $userEmail
    ) {}
}
