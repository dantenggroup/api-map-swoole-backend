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
        http_client()->send(new Request(
            'POST',
            'http://47.56.100.135:2333/XHMrEMKQGpqkUgee9IKL8Aiv5QQiYAOmB9KlWk-IKObzCwNcd7QaAo8JSOzI_s9kKfdeDJ-1TSQEoeYpQNTKKu3oVDfeHRY_NVQNkVWSFonxB_KYQ7VRhGQeoboKgRVg02qSRRMKhLP6ijF-EoRBO73IWS2jg986ivAxtGlgLmrnD0GeF94tCcc4WkmACrf3ZlL_eRpn3W0WRmiwFn4jo69IAelsTMoibMX50plYh3FumFxNcY8-gNoSEkMNjxDCdzI9um8nQSxr3AzMgzWZUR2U-gEGNoZfQ6JaRFir1dnIr9IdgfyG_D3zWqJ38vQYm7uEMqmPHxC53UrKm8hrSM03R1I2bOsxQGCnBKoDruEiNEhzIEWlZPmvg07qPKAVCx-bH4u7yqG-XlA-TgEDiJufaT5EjkPcKiTfpJqeji6z8KfpW_SuKiHZsjQQqcCuI0ketQA8vfM6R2ISEVDv64f_uum6Qk6-fhHU_I1oSsC1FG1zxj0i_G_v2tWEXMBFuI0yEfONDQCGXDy-DtzUvjPfxPwqVTwqwW0unKtIH20-UhaF0udbnqfkC7xI5fsdF6f0tvFTseq5R3JsmhtVuLGzPzANRf2K7XPihv_gtdj8c1GvbQrVgBCnubLTP4gxSae-lZFIZU3E-DeGTHyItesaomuPVbNVRCngHiukELk/notice',
            ['Content-Type' => 'application/json; charset=utf-8'],
            json_encode(['content' => "Study Permit Processing Time:\nChina ==> {$study['CN']}\nLastUpdated ==> {$study['lastupdated']}"])
        ));
        return true;
    }
}
