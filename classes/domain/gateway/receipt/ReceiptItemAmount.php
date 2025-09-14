<?php

namespace PalPalych\Payments\Classes\Domain\Gateway\Receipt;

use DomainException;

class ReceiptItemAmount
{
    public function __construct(
        public readonly int $value,
        public readonly ReceiptItemCurrency $currency,
    )
    {
        if ($this->value < 0) {
            throw new DomainException('Цена в чеке не может быть меньше нуля');
        }
    }
}
