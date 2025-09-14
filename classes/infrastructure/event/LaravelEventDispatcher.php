<?php

namespace PalPalych\Payments\Classes\Infrastructure\Event;

use Event;
use PalPalych\Payments\Classes\Domain\Event\EventDispatcherInterface;
use PalPalych\Payments\Classes\Domain\Event\EventInterface;

class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(EventInterface $event): void
    {
        Event::fire($event::name(), $event->args());
    }
}

