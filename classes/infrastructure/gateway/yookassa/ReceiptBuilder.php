<?php

namespace PalPalych\Payments\Classes\Infrastructure\Gateway\Yookassa;

use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItems;
use YooKassa\Model\Receipt\Receipt;
use YooKassa\Model\Receipt\ReceiptCustomer;
use YooKassa\Model\Receipt\ReceiptItem;

class ReceiptBuilder
{
    public function build(ReceiptItems $items, string $client_email): Receipt
    {
        $receipt = new Receipt();
        $receipt->setCustomer(new ReceiptCustomer(['email' => $client_email]));

        foreach ($items->getItems() as $domainReceiptItem) {
            $yookassaReceiptItem = new ReceiptItem();
            $yookassaReceiptItem->setDescription($domainReceiptItem->description);
            $yookassaReceiptItem->setQuantity($domainReceiptItem->quantity);
            $yookassaReceiptItem->setVatCode($domainReceiptItem->vatCode->value);
            $yookassaReceiptItem->setPaymentSubject($domainReceiptItem->paymentSubject->value);
            $yookassaReceiptItem->setPaymentMode('full_payment');
            $yookassaReceiptItem->setPrice([
                'value' => number_format($domainReceiptItem->amount->value / 100, 2, '.', ''),
                'currency' => $domainReceiptItem->amount->currency->value,
            ]);

            $receipt->addItem($yookassaReceiptItem);
        }

        return $receipt;
    }
}
