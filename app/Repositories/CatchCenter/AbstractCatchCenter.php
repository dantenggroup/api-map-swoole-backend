<?php

declare(strict_types=1);

namespace App\Repositories\CatchCenter;

abstract class AbstractCatchCenter
{
    protected int $timeout = 50000;

    abstract protected function getKey(): string;

    abstract protected function data(bool $is_update = false);

    final public function getData(bool $is_update = false)
    {
        $redis = redis_catch();
        $key = get_class($this) . $this->getKey();
        $version = $is_update ? $redis->incr($key) : (int)$redis->get($key);
        go(function () use ($redis, $key) {
            $redis->expire($key, 500000);
        });
        return sp_catch("center:{$key}:{$version}", function () use ($is_update) {
            return $this->data($is_update);
        }, $this->timeout, false);
    }

    public function dispatch()
    {
        event_dispatcher($this);
    }
}