<?php

declare(strict_types=1);

namespace PalPalych\Payments\Tests\Unit\Entities;

use PalPalych\Payments\Classes\Domain\Gateway\Receipt\VatCode;
use PalPalych\Payments\Classes\Domain\Contract\PayableInterface;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItem;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItems;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItemAmount;
use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItemCurrency;

class TestPayableEntity implements PayableInterface
{
    public function getPayableId(): string
    {
        return '1';
    }

    public function getPayableAmount(): int
    {
        return 1000;
    }

    public function getPayableDescription(): string
    {
        return 'Test payable entity';
    }

    public function markAsPaid(): void
    {
    }

    public function getReceiptItems(): ReceiptItems
    {
        $receiptItems = new ReceiptItems();
        $receiptItems->addItem(new ReceiptItem(
            'test item',
            new ReceiptItemAmount(
                $this->getPayableAmount(),
                ReceiptItemCurrency::rub,
            ),
            VatCode::without_vat,
            1
        ));

        return $receiptItems;
    }

}
