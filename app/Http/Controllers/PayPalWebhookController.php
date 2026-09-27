<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PaymentGatewayInterface;
use App\Jobs\ActivateLicenseJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PayPalWebhookController
{
    public function __construct(private PaymentGatewayInterface $gateway) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $headers = array_change_key_case($request->headers->all(), CASE_LOWER);

        if (!$this->gateway->verifyWebhookSignature($payload, $headers)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if ($request->input('event_type') !== 'PAYMENT.CAPTURE.COMPLETED') {
            return response()->json(['status' => 'ignored']);
        }

        $paypalOrderId = $request->input('resource.id');
        $lock = Cache::lock("payment_processing:{$paypalOrderId}", 10);

        if (!$lock->get()) {
            return response()->json(['status' => 'processing in parallel'], 202);
        }

        ActivateLicenseJob::dispatch(
            paypalOrderId: $paypalOrderId,
            amountCents: (int) ($request->input('resource.amount.value') * 100),
            licenseType: $request->input('resource.purchase_units.0.custom_id', 'personal'),
            userEmail: $request->input('resource.payer.email_address')
        )->onQueue('payments');

        return response()->json(['status' => 'enqueued']);
    }
}

