<?php

namespace PalPalych\Payments\Classes\Application\Dto\Request;

class HandleWebhookRequest
{
    public function __construct(
        public array $payload,
        public string $ip,
        public bool $more_logs,
    )
    {

    }
}
