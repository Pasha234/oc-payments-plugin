<?php

namespace PalPalych\Payments\Classes\Domain\Dto\Gateway;

class CreatePaymentMethodGatewayResponse
{
    public function __construct(
        public string $gateway_request,
        public string $gateway_response,
        public string $gateway_id,
        public string $idempotence_key,
        public string $confirmation_url,
        public ?string $card_type = null,
        public ?string $last4 = null,
        public ?string $expiry_year = null,
        public ?string $expiry_month = null,
    )
    {

    }
}
