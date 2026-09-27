<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\License;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivateLicenseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 15;

    // Прайс-лист для Price Integrity проверки (в реальном коде берется из таблицы продуктов в БД)
    private const array PRODUCT_PRICES = [
        'personal' => 4900,     // $49.00
        'ultimate_ide' => 19900 // $199.00
    ];

    public function __construct(
        public string $paypalOrderId,
        public int $amountCents,
        public string $licenseType,
        public string $userEmail
    ) {}

    public function handle(): void
    {
        if (Payment::where('transaction_id', $this->paypalOrderId)->exists()) {
            return;
        }

        // Лаконичная проверка Price Integrity
        $expectedPrice = self::PRODUCT_PRICES[$this->licenseType] ?? null;

        if ($expectedPrice === null || $this->amountCents !== $expectedPrice) {
            Log::alert('Payment Price Integrity Violation Detected', [
                'paypal_order_id' => $this->paypalOrderId,
                'received_cents' => $this->amountCents,
                'expected_cents' => $expectedPrice,
                'license_type' => $this->licenseType,
                'user_email' => $this->userEmail,
            ]);

            Payment::create([
                'transaction_id' => $this->paypalOrderId,
                'amount_cents' => $this->amountCents,
                'status' => 'failed_price_mismatch',
                'user_email' => $this->userEmail,
            ]);

            return; // Прерываем выполнение, лицензия выдана не будет
        }

        DB::transaction(function () {
            $payment = Payment::create([
                'transaction_id' => $this->paypalOrderId,
                'amount_cents' => $this->amountCents,
                'status' => 'completed',
                'user_email' => $this->userEmail,
            ]);

            License::create([
                'payment_id' => $payment->id,
                'license_key' => 'JB-' . strtoupper(Str::random(16)),
                'type' => $this->licenseType,
                'expires_at' => now()->addYear(),
            ]);
        });
    }
}
