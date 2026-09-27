<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\PayPalGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayInterface::class, function ($app) {
            return new PayPalGateway(
                clientId: config('services.paypal.client_id'),
                clientSecret: config('services.paypal.client_secret'),
                baseUrl: config('services.paypal.base_url')
            );
        });
    }
}
