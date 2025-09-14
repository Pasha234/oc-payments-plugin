<?php

namespace PalPalych\Payments\Classes\Application\Dto\Response;

class CreatePaymentMethodResponse
{
    public function __construct(
        public string $confirmation_url,
        public int $payment_method_id,
    )
    {

    }
}
