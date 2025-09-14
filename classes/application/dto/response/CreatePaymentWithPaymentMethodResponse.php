<?php

namespace PalPalych\Payments\Classes\Application\Dto\Response;

class CreatePaymentWithPaymentMethodResponse
{
    public function __construct(
        public int $payment_id,
    )
    {

    }
}
