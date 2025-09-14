<?php

namespace PalPalych\Payments\Classes\Application\Dto\Request;

class CreatePaymentRequest
{
    public function __construct(
        public readonly int $userId,
        public readonly int|string $payableId,
        public readonly string $payableType,
        public readonly string $success_url,
        public readonly string $client_email,
    ) {
    }
}
