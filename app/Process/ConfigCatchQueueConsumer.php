<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Process;

use Hyperf\AsyncQueue\Process\ConsumerProcess;
use Hyperf\Process\Annotation\Process;

/**
 * @Process
 */
class ConfigCatchQueueConsumer extends ConsumerProcess
{
    protected string $version = '1.0';

    public function handle(): void
    {
        while (true) {
            $version = config('version', '');
            if ($version != '') {
                study_notice_list($this->version == $version);
                $this->version = $version;
            }
            sleep(1);
        }
    }
}
