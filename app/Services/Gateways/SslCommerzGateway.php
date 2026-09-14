<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;

class SslCommerzGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly ?string $storeId,
        private readonly ?string $storePassword,
    ) {
    }

    public function charge(int $amountInCents): array
    {
        return [
            'status' => 'simulated',
            'gateway' => 'sslcommerz',
            'amount' => $amountInCents,
            'store_id' => $this->storeId,
        ];
    }
}
