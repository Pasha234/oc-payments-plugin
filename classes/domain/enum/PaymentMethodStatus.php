<?php

namespace PalPalych\Payments\Classes\Domain\Enum;

enum PaymentMethodStatus: int
{
    case pending = 0;
    case success = 1;
    case canceled = 2;
    case failed = 3;

    public static function getOptions(): array
    {
        return [
            PaymentMethodStatus::pending->value => 'Ожидает',
            PaymentMethodStatus::success->value => 'Успешно',
            PaymentMethodStatus::canceled->value => 'Отменено',
            PaymentMethodStatus::failed->value => 'Ошибка',
        ];
    }
}
