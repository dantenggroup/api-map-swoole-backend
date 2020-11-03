<?php

declare(strict_types=1);

namespace App\Repositories\Queue;

use Carbon\Carbon;
use Exception;

abstract class AbstractQueue
{
    abstract protected function handle();

    public function run(int $attempts = 1)
    {
        if ($attempts <= 0) {
            db_main('failed_jobs')->insert([
                'queue' => get_class($this),
                'payload' => serialize($this),
                'failed_reason' => 'attempts number is zero'
            ]);
            return;
        }
        try {
            $this->handle();
        } catch (Exception $e) {
            $this->run($attempts - 1);
            db_main('failed_jobs')->insert([
                'queue' => get_class($this),
                'payload' => serialize($this),
                'failed_reason' => $e->getMessage(),
            ]);
        }
    }

    protected function queue(int $attempts = 1, Carbon $available_at = null)
    {
        if (empty($available_at)) {
            $available_at = Carbon::now();
        }

        if ($attempts < 1) {
            $attempts = 1;
        }

        db_main('jobs')->insert([
            'queue' => get_class($this),
            'payload' => serialize($this),
            'attempts' => $attempts,
            'available_at' => $available_at
        ]);
    }
}