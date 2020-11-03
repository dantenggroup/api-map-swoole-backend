<?php

declare(strict_types=1);

namespace App\Repositories\Tools;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class CoroutineLimit
{
    /**
     * @var bool
     */
    private bool $is_finished = false;

    /**
     * @var Channel
     */
    private Channel $channel;


    public function __construct(int $limit_number)
    {
        $this->channel = new Channel($limit_number * 100);
        for ($i = 0; $i < $limit_number; $i++) {
            go(function () {
                while (true) {
                    if ($this->is_finished) {
                        break;
                    }
                    $callable = $this->channel->pop();
                    if (is_callable($callable)) {
                        call_user_func($callable);
                    }
                }
            });
        }
        go(function () use ($limit_number) {
            Coroutine::sleep(15);
            while (true) {
                Coroutine::sleep(1);
                $status = $this->channel->stats();
                if ($status['consumer_num'] == $limit_number && $status['queue_num'] == 0) {
                    $this->is_finished = true;
                    $this->channel->close();
                    break;
                }
            }
        });
    }

    public function call(callable $callable)
    {
        $this->channel->push($callable);
    }
}
