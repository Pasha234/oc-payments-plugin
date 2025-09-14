<?php

namespace PalPalych\Payments\Classes\Domain\Event;

interface EventInterface
{
    public static function name(): string;

    public function args(): array;
}
