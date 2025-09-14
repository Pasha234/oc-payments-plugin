<?php

namespace PalPalych\Payments\Classes\Domain\Dto\Gateway;

use PalPalych\Payments\Classes\Domain\Enum\PaymentMethodStatus;

class CheckPaymentMethodGatewayResponse
{
    public function __construct(
        public PaymentMethodStatus $status,
        public string $gateway_response,
        public ?string $card_type = null,
        public ?string $last4 = null,
        public ?string $expiry_year = null,
        public ?string $expiry_month = null,
    )
    {

    }
}
