<?php

declare(strict_types=1);

namespace App\Command\Generator;

use App\Command\CrontabBase;
use App\Repositories\Corn\CronSchedule;
use App\Repositories\Corn\Language\BaseLanguage;
use App\Repositories\Corn\Language\English;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Utils\ApplicationContext;
use RuntimeException;

/**
 * @Command
 */
class PublishCrontabCommand extends HyperfCommand
{
    protected int $num = 1;

    protected BaseLanguage $language;
    /**
     * @var false|resource
     */
    protected $file;

    public function __construct()
    {
        parent::__construct('publish:crontab-command');
        $this->setDescription('Publish the crontab commands');
    }

    public function handle()
    {
        $publish_path = '/tmp/crontab_publish';
        $this->language = new English();
        unlink($publish_path);
        $this->file = fopen($publish_path, 'a');
        $container = ApplicationContext::getContainer();
        $this->line("Publish the crontab commands:\n", 'info');
        $annotationCommands = AnnotationCollector::getClassesByAnnotation(Command::class);

        collect($annotationCommands)->keys()->filter(function ($class) {
            return preg_match('/^App\\\Command\\\Crontab\\\\D*/', $class, $matches, PREG_OFFSET_CAPTURE);
        })->each(function ($class) use ($container) {
            $command = $container->get($class);
            if ($command instanceof CrontabBase) {
                $info = $command->getInfo();
                if ($command->getExpression()->getExpression() == '* * * * *') {
                    throw new RuntimeException("{$info['name']} 没有配置定时运行");
                }
                $run_time = CronSchedule::fromCronExpression($command->getExpression(),
                    $this->language)->asNaturalLanguage();
                $repeat = $info['can_repeat'] ? '可重复运行' : '不可重复运行';
                $base_path = BASE_PATH;
                fwrite($this->file, "# {$this->num}: {$info['description']} {$run_time} {$repeat}\n");
                fwrite($this->file, "{$info['expression']} /usr/bin/php {$base_path}/bin/hyperf.php {$info['name']} > /dev/null\n");
                $this->line("{$this->num}: {$info['name']}    {$info['description']} ", 'info');
                $this->line("{$run_time} {$repeat} \n");
                $this->num++;
            }
        });
    }
}
