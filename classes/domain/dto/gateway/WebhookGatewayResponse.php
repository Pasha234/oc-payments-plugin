<?php

namespace PalPalych\Payments\Classes\Domain\Dto\Gateway;

use PalPalych\Payments\Classes\Domain\Gateway\PaymentGatewayWebhookStatus;

class WebhookGatewayResponse
{
    public function __construct(
        public PaymentGatewayWebhookStatus $status,
        public string $gateway_id,
        public string $gateway_response,
    )
    {

    }
}
