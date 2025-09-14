<?php

namespace PalPalych\Payments\Classes\Domain\Event;

use PalPalych\Payments\Classes\Domain\Entity\PaymentMethod;

class PaymentMethodActivated implements EventInterface
{
    public function __construct(
        public readonly PaymentMethod $paymentMethod
    ) {
    }

    public static function name(): string
    {
        return 'payment_method.activated';
    }

    public function args(): array
    {
        return [
            $this->paymentMethod,
        ];
    }
}

