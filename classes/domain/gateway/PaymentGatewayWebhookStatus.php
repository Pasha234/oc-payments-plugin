<?php

namespace PalPalych\Payments\Classes\Domain\Gateway;

enum PaymentGatewayWebhookStatus: string
{
    case payment_waiting_for_capture = 'payment.waiting_for_capture';
    case payment_success = 'payment.succeeded';
    case payment_canceled = 'payment.canceled';
    case payment_method_active = 'payment_method.active';
}
