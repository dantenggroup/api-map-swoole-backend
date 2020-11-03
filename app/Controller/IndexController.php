<?php

declare(strict_types=1);

namespace App\Controller;

class IndexController extends AbstractController
{
    public function index()
    {
        return $this->successResponse([
            'version' => '0.0.001',
            'name' => 'api-map',
            'ip' => get_client_ip(),
            'framework' => 'hyperf',
            'level' => env('APP_ENV'),
            'time' => time(),
            'config' => config('test')
        ]);
    }
}
