<?php

namespace PalPalych\Payments\Classes\Domain\Gateway\Receipt;

enum ReceiptItemCurrency: string
{
    case rub = 'RUB';
    case usd = 'USD';
    case eur = 'EUR';
}
