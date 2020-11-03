<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\ScriptLog;
use App\Repositories\Tools\ManagesFrequencies;
use Carbon\Carbon;
use Cron\CronExpression;
use Exception;
use Hyperf\Utils\Coroutine;
use Ramsey\Uuid\Uuid;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

abstract class CrontabBase extends HyperfCommand
{
    use ManagesFrequencies;

    abstract protected function register();
    /**
     * The cron expression representing the script frequency.
     * @var string
     */
    protected string $expression = '* * * * *';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description;

    /**
     * 能否重复执行
     * @var bool
     */
    protected bool $can_repeat = false;

    /**
     * 脚本运行的唯一值
     * @var string
     */
    protected string $uuid;

    /**
     * 脚本运行日志
     * @var ScriptLog
     */
    protected ScriptLog $log;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->register();
        $this->setDescription($this->description);
        parent::__construct($this->name);
        $this->uuid = Uuid::uuid4()->toString();
        $log = new ScriptLog();
        $log->uuid = $this->uuid;
        $log->run_as = env('DB_USERNAME');
        $this->log = $log;
    }

    /**
     * 获取脚本信息
     * @return array
     */
    public function getInfo()
    {
        return [
            'name' => $this->name,
            'description' => $this->getDescription(),
            'expression' => $this->expression,
            'can_repeat' => $this->can_repeat,
        ];
    }

    /**
     * Get the cron expression representing the script frequency.
     * @return CronExpression
     */
    public function getExpression()
    {
        return CronExpression::factory($this->expression);
    }

    /**
     * 记录运行时日志
     * @param $message
     * @param array $context
     */
    public function log($message, $context = array())
    {
        $uuid = $this->uuid;
        $context = serialize($context);
        db_main('script_sub_logs')->insert(compact('uuid', 'message', 'context'));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|mixed
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->isRun() && !$this->can_repeat) {
            $output->write('此脚本已经运行过', true);
            return 0;
        }
        $name = $this->name;
        $description = $this->description;
        $type = 1;
        $run_time = 0;
        $run_result = '';
        $callback = function () use (&$type, &$run_time, &$run_result) {
            $start_time = microtime(true);
            try {
                $run_result = call([$this, 'handle']);
            } catch (Throwable $exception) {
                $type = $exception->getCode();
                $run_result = $exception->getMessage();
            }
            $run_time = intval((microtime(true) - $start_time) * 1000);
        };

        if ($this->coroutine && !Coroutine::inCoroutine()) {
            run($callback, $this->hookFlags);
        } else {
            $callback();
        }

        $this->log->name = $name;
        $this->log->description = $description;
        $this->log->run_time_ms = $run_time;
        $this->log->type = $type;
        $this->log->result = serialize($run_result);
        $this->log->class_name = get_class($this);
        $this->log->save();
        $output->write("{$name} <::> {$description} => run: {$run_time}ms", true);
        return 1;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function isRun()
    {
        $express = $this->getExpression();
        $start = Carbon::instance($express->getPreviousRunDate());
        $end = Carbon::instance($express->getNextRunDate());
        return ScriptLog::query()->where('name', $this->name)->where('type', 1)
            ->whereBetween('created_at', [$start, $end])->exists();
    }
}
