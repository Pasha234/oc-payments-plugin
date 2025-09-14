<?php

namespace PalPalych\Payments\Classes\Domain\Dto\Gateway;

use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use PalPalych\Payments\Classes\Domain\Entity\Payment;

class CreatePaymentWithPaymentMethodGatewayRequest
{
    public function __construct(
        public Payment $payment,
        public PayableInterface $payable,
        public string $paymentMethodGatewayId,
        public string $client_email,
        public string|null $description = null,
    )
    {

    }
}
