<?php

declare(strict_types=1);

namespace App\Repositories\Events;

abstract class AbstractEvent
{
    abstract public function deal();

    public function dispatch()
    {
        event_dispatcher($this);
    }
}