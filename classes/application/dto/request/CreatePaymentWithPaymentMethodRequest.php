<?php

namespace PalPalych\Payments\Classes\Application\Dto\Request;

class CreatePaymentWithPaymentMethodRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly int|string $payableId,
        public readonly string $payableType,
        public readonly int $paymentMethodId,
        public readonly string $client_email,
    ) {
    }
}
