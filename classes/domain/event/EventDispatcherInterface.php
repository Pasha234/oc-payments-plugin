<?php

namespace PalPalych\Payments\Classes\Domain\Event;

interface EventDispatcherInterface
{
    public function dispatch(EventInterface $event): void;
}

