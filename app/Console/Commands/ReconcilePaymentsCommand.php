<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\PaymentGatewayInterface;
use App\Jobs\ActivateLicenseJob;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile';
    protected $description = 'Синхронизация зависших в статусе pending платежей с API PayPal';

    public function handle(PaymentGatewayInterface $gateway): void
    {
        $pendingPayments = Payment::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(30))
            ->get();

        foreach ($pendingPayments as $payment) {
            try {
                $paypalStatus = $gateway->getOrderStatus($payment->transaction_id);

                if (in_array($paypalStatus, ['COMPLETED', 'APPROVED'], true)) {
                    ActivateLicenseJob::dispatch(
                        paypalOrderId: $payment->transaction_id,
                        amountCents: (int) $payment->amount_cents,
                        licenseType: 'personal', 
                        userEmail: $payment->user_email
                    )->onQueue('payments');

                    Log::info("Payment {$payment->transaction_id} pushed to activation via reconciliation cron.");
                }
            } catch (\Throwable $e) {
                Log::error("Error processing reconciliation for payment {$payment->id}", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
    }
}
