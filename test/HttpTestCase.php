<?php

declare(strict_types=1);

namespace HyperfTest;

use Closure;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\Client;
use Hyperf\Utils\Arr;
use PHPUnit\Framework\TestCase;

/**
 * Class HttpTestCase.
 * @method get($uri, $data = [], $headers = [])
 * @method post($uri, $data = [], $headers = [])
 * @method json($uri, $data = [], $headers = [])
 * @method file($uri, $data = [], $headers = [])
 */
abstract class HttpTestCase extends TestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected bool $is_show_return = true;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->client = make(Client::class);
    }

    public function __call($name, $arguments)
    {
        return $this->client->{$name}(...$arguments);
    }


    public function assertCode(array $data)
    {
        $this->assertTrue(
            Arr::get($data, 'code', 0) == 200,
            Arr::get($data, 'message', 'null message')
        );
    }

    /**
     * 输出标识文本
     * @param string $info
     */
    public function info(string $info)
    {
        dump($info);
    }


    public function spPost(string $uri, array $data = [], array $headers = [])
    {
        dump(" \n________________________________________________________________");
        $this->info('POST URL：' . $uri);
        if (count($data) > 0) {
            $this->info('参数:');
            dump($data);
        }
        $data = $this->post($uri, $data, $headers);
        $this->assertCode($data);
        if ($this->is_show_return) {
            $this->info('返回值:');
            dump($data['data']);
            $this->is_show_return = true;
        }
        return $data['data'];
    }

    public function spGet(string $uri, array $data = [], array $headers = [])
    {
        dump(" \n________________________________________________________________");
        $this->info('GET URL：' . $uri);
        if (count($data) > 0) {
            $this->info('参数:');
            dump($data);
        }
        $data = $this->get($uri, $data, $headers);
        $this->assertCode($data);
        if ($this->is_show_return) {
            $this->info('返回值:');
            dump($data['data']);
            $this->is_show_return = true;
        }
        return $data['data'];
    }

    /**
     * @param Closure $function
     */
    public function transaction(Closure $function)
    {
        Db::beginTransaction();
        $function();
        Db::rollBack();
    }
}
