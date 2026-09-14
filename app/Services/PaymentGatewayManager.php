<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Contracts\Foundation\Application;

class PaymentGatewayManager
{
    public function __construct(private readonly Application $app)
    {
    }

    public function gateway(): PaymentGatewayInterface
    {
        return $this->app->make(PaymentGatewayInterface::class);
    }
}
