<?php

namespace PalPalych\Payments\Classes\Application\Dto\Request;

class DeletePaymentMethodRequest
{
    public function __construct(
        public string $payment_method_id,
    )
    {

    }
}
