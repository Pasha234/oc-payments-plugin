<?php

namespace PalPalych\Payments\Classes\Application\Dto\Response;

class CreatePaymentResponse
{
    public function __construct(
        public string $confirmation_url,
    )
    {

    }
}
