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
return [
    'enable' => env('ALIYUN_ACM_ENABLE', false),
    'use_standalone_process' => true,
    'interval' => 5,
    'endpoint' => env('ALIYUN_ACM_ENDPOINT', 'addr-hk-internal.edas.aliyuncs.com'),
    'namespace' => '10bb6b0f-4791-4ef4-b3fb-52cac6bcd1a3',
    'data_id' => 'hyperf',
    'group' => 'api-map',
    'access_key' => env('ALIYUN_ACM_AK', ''),
    'secret_key' => env('ALIYUN_ACM_SK', ''),
];
