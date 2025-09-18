<?php

namespace PalPalych\Payments\Classes\Application\Dto\Response;

use PalPalych\Payments\Classes\Domain\Enum\PaymentStatus;

class CheckPaymentResponse
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $confirmation_url = null
    ) {
    }
}