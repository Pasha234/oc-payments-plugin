<?php

namespace PalPalych\Payments\Classes\Domain\Dto\Gateway;

use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;

class CreatePaymentWithPaymentMethodGatewayResponse
{
    public function __construct(
        public string $gateway_request,
        public string $gateway_response,
        public string $gateway_id,
        public string $idempotence_key,
        public PaymentStatus $status,
    )
    {

    }
}
