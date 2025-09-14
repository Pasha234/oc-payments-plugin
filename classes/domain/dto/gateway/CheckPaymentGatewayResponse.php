<?php

namespace PalPalych\Payments\Classes\Domain\Dto\Gateway;

use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;

class CheckPaymentGatewayResponse
{
    public function __construct(
        public PaymentStatus $status,
        public string $gateway_response,
    )
    {

    }
}
