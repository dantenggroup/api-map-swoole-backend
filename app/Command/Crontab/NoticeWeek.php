<?php

declare(strict_types=1);

namespace App\Command\Crontab;

use App\Command\CrontabBase;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Hyperf\Command\Annotation\Command;

/**
 * @Command
 */
class NoticeWeek extends CrontabBase
{
    /**
     * 脚本说明
     * @var string
     */
    protected string $description = '学签时间';

    /**
     * 能否重复执行，默认不能重复执行
     * @var bool
     */
    protected bool $can_repeat = true;

    /**
     * 注册命令，分配执行频率
     * 如：
     *    $this->dailyAt('02:30');
     *    $this->weeklyOn(1); or $this->weeklyOn(1, '02:30');
     *    $this->monthlyOn(1); or $this->monthlyOn(1, '02:30');
     */
    protected function register()
    {
        $this->name = 'crontab:notice_week';
        $this->dailyAt('08:30');
    }

    /**
     * Execute the console command.
     *
     * @return mixed 返回值可以被记录
     * @throws GuzzleException
     */
    public function handle()
    {
        $response = http_client()->get('https://www.canada.ca/content/dam/ircc/documents/json/data-ptime-en.json');
        $json = json_decode($response->getBody()->getContents(), true);
        $study = $json['study'];
        $content = json_encode([
            'content' => "Study Permit Processing Time:\nChina ==> {$study['CN']}\nLastUpdated ==> {$study['lastupdated']}"
        ]);
        $notice_list = (array)config('study_notice_list');
        foreach ($notice_list as $url) {
            http_client()->send(new Request(
                'POST',
                $url,
                ['Content-Type' => 'application/json; charset=utf-8'],
                $content
            ));
        }
        return true;
    }
}
