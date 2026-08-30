<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PaymentGatewayManager;
use App\Contracts\PaymentGatewayInterface;
use App\Services\Gateways\SslCommerzGateway;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * register(): ONLY bind things into the container here.
     * Do NOT resolve/use other services here — they might not be registered yet.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayInterface::class, function ($app) {
            return new SslCommerzGateway(
                config('services.sslcommerz.store_id'),
                config('services.sslcommerz.store_password'),
            );
        });

        $this->app->bind(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager($app);
        });
    }

    /**
     * boot(): runs AFTER all providers' register() methods have fired.
     * Safe to resolve other bindings, add routes, publish config, listen to events.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/payment.php' => config_path('payment.php'),
        ], 'payment-config');

        $this->app['router']->aliasMiddleware('payment.verify', \App\Http\Middleware\VerifyPaymentSignature::class);
    }
}