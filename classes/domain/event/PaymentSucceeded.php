<?php

namespace PalPalych\Payments\Classes\Domain\Event;

use PalPalych\Payments\Classes\Domain\Entity\Payment;

class PaymentSucceeded implements EventInterface
{
    public function __construct(
        public readonly Payment $payment
    ) {
    }

    public static function name(): string
    {
        return 'payment.succeed';
    }

    public function args(): array
    {
        return [
            $this->payment
        ];
    }
}

