<?php

namespace PalPalych\Payments\Classes\Application\Dto\Request;

class CheckPaymentMethodRequest
{
    public function __construct(
        public string $payment_method_id,
    )
    {

    }
}
