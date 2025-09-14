<?php

namespace PalPalych\Payments\Classes\Domain\Gateway\Receipt;

enum PaymentSubject: string
{
    case commodity = 'commodity';
    case service = 'service';
}
