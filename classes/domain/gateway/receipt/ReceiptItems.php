<?php

namespace PalPalych\Payments\Classes\Domain\Gateway\Receipt;

class ReceiptItems
{
    /** @var array<int, ReceiptItem> $items */
    private array $items = [];

    public function addItem(ReceiptItem $item) {
        $this->items[] = $item;
    }

    /**
     * @return array<int, ReceiptItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
