<?php

namespace PalPalych\Payments\Classes\Domain\Contract;

use PalPalych\Payments\Classes\Domain\Gateway\Receipt\ReceiptItems;

interface PayableInterface
{
    public function getPayableId(): string;

    public function getPayableAmount(): int;

    public function getPayableDescription(): string;

    public function markAsPaid(): void;

    public function getReceiptItems(): ReceiptItems;
}
