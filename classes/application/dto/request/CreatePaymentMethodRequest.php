<?php

namespace PalPalych\Payments\Classes\Application\Dto\Request;

class CreatePaymentMethodRequest
{
    public function __construct(
        public int $userId,
        public string $success_url,
    )
    {
    }
}
