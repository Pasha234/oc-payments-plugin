<?php

namespace PalPalych\Payments\Classes\Domain\Gateway\Receipt;

use DomainException;

class ReceiptItem
{
    public function __construct(
        public string $description,
        public ReceiptItemAmount $amount,
        public VatCode $vatCode,
        public int $quantity,
        public PaymentSubject $paymentSubject = PaymentSubject::service,
    )
    {
        if ($quantity <= 0) {
            throw new DomainException('Количество товара в чеке не может быть меньше 1');
        }
    }

}
