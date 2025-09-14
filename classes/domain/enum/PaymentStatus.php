<?php

namespace PalPalych\Payments\Classes\Domain\Enum;

enum PaymentStatus: int
{
    case pending = 0;
    case success = 1;
    case canceled = 2;
    case failed = 3;

    public static function getOptions(): array
    {
        return [
            PaymentStatus::pending->value => 'Ожидает',
            PaymentStatus::success->value => 'Успешно',
            PaymentStatus::canceled->value => 'Отменено',
            PaymentStatus::failed->value => 'Ошибка',
        ];
    }
}
