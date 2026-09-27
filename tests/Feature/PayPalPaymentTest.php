<?php

namespace Tests\Feature;

use App\Jobs\ActivateLicenseJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PayPalPaymentTest extends TestCase
{
    public function test_webhook_verifies_signature_and_enqueues_job(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/webhooks/paypal', [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAPTURE-999',
                'amount' => ['value' => '49.00'],
                'payer' => ['email_address' => 'developer@jetbrains.com'],
                'purchase_units' => [['custom_id' => 'personal']]
            ]
        ], [
            'paypal-transmission-id' => 'mock-id-123'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'enqueued']);

        Queue::assertPushed(ActivateLicenseJob::class, function ($job) {
            return $job->paypalOrderId === 'CAPTURE-999' 
                && $job->amountCents === 4900
                && $job->userEmail === 'developer@jetbrains.com';
        });
    }
}
