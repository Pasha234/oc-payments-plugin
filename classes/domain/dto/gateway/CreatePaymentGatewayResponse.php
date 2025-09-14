<?php

namespace PalPalych\Payments\Classes\Domain\Dto\Gateway;

class CreatePaymentGatewayResponse
{
    public function __construct(
        public string $gateway_request,
        public string $gateway_response,
        public string $gateway_id,
        public string $idempotence_key,
        public string $confirmation_url,
    )
    {

    }
}
