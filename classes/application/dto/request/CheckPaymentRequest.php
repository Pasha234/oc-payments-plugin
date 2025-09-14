<?php

namespace PalPalych\Payments\Classes\Application\Dto\Request;

class CheckPaymentRequest
{
    public function __construct(
        public string $payment_id,
    )
    {

    }
}
