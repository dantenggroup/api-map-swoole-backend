<?php
declare(strict_types=1);

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @param string $table
 * @return Builder
 */
function db_main(string $table)
{
    return Db::connection('default')->table($table);
}

/**
 * @return RedisProxy|Redis|null
 */
function redis_main()
{
    return ApplicationContext::getContainer()->get(RedisFactory::class)->get('default');
}

/**
 * @return RedisProxy|Redis|null
 */
function redis_catch()
{
    return ApplicationContext::getContainer()->get(RedisFactory::class)->get('catch');
}

/**
 * @return RedisProxy|Redis|null
 */
function redis_queue()
{
    return ApplicationContext::getContainer()->get(RedisFactory::class)->get('queue');
}


/**
 * @return ClientBuilder
 */
function es_builder()
{
    $container = ApplicationContext::getContainer();
    return $container->get(ClientBuilderFactory::class)->create();
}

/**
 * @param array $hosts
 * @return Client
 */
function es_client(array $hosts)
{
    return es_builder()->setHosts($hosts)->build();
}


/**
 * @param array $options
 * @return \GuzzleHttp\Client
 */
function http_client(array $options = [])
{
    $container = ApplicationContext::getContainer();
    return $container->get(ClientFactory::class)->create($options);
}


if (!function_exists('null_with_value')) {
    function null_with_value($value, Closure $callback, $default = null)
    {
        return $value == null ? $default : $callback($value);
    }
}

if (!function_exists('is_phone_number')) {
    function is_phone_number($phone)
    {
        return (bool)preg_match('/^1[3-9]\d{9}$/', $phone);
    }
}

if (!function_exists('sp_catch')) {
    /**
     * 缓存数据
     * @param string $cacheKey
     * @param Closure $callback
     * @param int $minutes
     * @param bool $is_update
     * @return mixed
     */
    function sp_catch(string $cacheKey, Closure $callback, int $minutes = 600, bool $is_update = false)
    {
        $redis = redis_catch();
        if ($is_update) {
            $version = $redis->incr("{$cacheKey}:version");
            go(function () use ($redis, $cacheKey) {
                $redis->expire("{$cacheKey}:version", 500000);
            });
            $value = $callback();
            $redis->setex("$cacheKey:$version", $minutes * 60, serialize($value));
            return $value;
        }
        $version = (int)$redis->get("{$cacheKey}:version");
        $value = $redis->get("$cacheKey:$version");
        if (empty($value) || $value == '') {
            $value = $callback();
            $redis->setex("$cacheKey:$version", $minutes * 60, serialize($value));
            return $value;
        }
        return unserialize($value);
    }
}

/**
 * Return passed regex rules string
 * @param string $string
 * @param array $regex
 *  \p{Han} : 中文
 *  \p{Z} : 空格或不可见的分隔符
 *  \p{M} : 字符附加的字符（eg, 拼音的声调）
 *  \p{N} : 数字
 *  \p{L} : Unicode 字符串， 如只过滤英文推荐使用 ‘a-zA-Z’
 *  \p{P} : 标点字符
 * @return string
 */
function string_pass(string $string, array $regex = ['\p{Han}', '\p{Z}', '\p{M}', '\p{N}', 'a-zA-Z'])
{
    return preg_replace(sprintf('~[^%s]~u', implode($regex)), '', $string);
}

/**
 * @param string $table
 * @param string $column
 * @param string $prefix
 * @param int $length
 * @return string
 */
function generate_unique_identifier(string $table, string $column, string $prefix = '', int $length = 50)
{
    try {
        $id = $prefix . bin2hex(random_bytes($length));
        if (db_main($table)->where($column, $id)->exists()) {
            return generate_unique_identifier($table, $column, $prefix, $length);
        }
        return $id;
    } catch (Exception $e) {
        return generate_unique_identifier($table, $column, $prefix, $length);
    }
}

/**
 * @param string $table
 * @param string $column
 * @param string $end
 * @param int $length
 * @return string
 */
function generate_unique_field(string $table, string $column, string $end = '', int $length = 50)
{
    try {
        $id = bin2hex(random_bytes($length)) . $end;
        if (db_main($table)->where($column, $id)->exists()) {
            return generate_unique_identifier($table, $column, $end, $length);
        }
        return $id;
    } catch (Exception $e) {
        return generate_unique_identifier($table, $column, $end, $length);
    }
}


/**
 * @param string $name
 * @return LoggerInterface
 * @throws Exception
 */
function get_log_client($name = 'default'): LoggerInterface
{
    $group = ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name);
    if ($group instanceof LoggerInterface) {
        return $group;
    } else {
        throw new Exception('wrong log client');
    }
}

/**
 * @return RequestInterface
 * @throws Exception
 */
function get_request(): RequestInterface
{
    $request = ApplicationContext::getContainer()->get(Hyperf\HttpServer\Contract\RequestInterface::class);
    if ($request instanceof RequestInterface) {
        return $request;
    } else {
        throw new Exception('Invalid Environment', 201);
    }
}


function event_dispatcher(object $event)
{
    return ApplicationContext::getContainer()->get(EventDispatcherInterface::class)->dispatch($event);
}

/**
 * @param Builder|Model|\Hyperf\Database\Model\Builder $builder
 * @param int $page
 * @param int $per_page
 * @param array|string[] $columns
 * @return array
 */
function page_builder($builder, int $page, int $per_page = 30, array $columns = ['*'])
{
    if ($page < 1) {
        $page = 1;
    }
    $total = (clone $builder)->count(['id']);
    return [
        'current_page' => $page,
        'data' => $builder->skip(($page - 1) * $per_page)->take($per_page + 1)->get($columns)->values()->toArray(),
        'last_page' => max((int)ceil($total / $per_page), 1),
        'per_page' => $per_page,
        'total' => $total,
    ];
}

/**
 * @param Closure $main
 * @param Closure|null $roll_back
 * @return mixed
 * @throws Exception
 */
function db_transaction(Closure $main, Closure $roll_back = null)
{
    DB::beginTransaction();
    try {
        $result = $main();
        DB::commit();
        return $result;
    } catch (Exception $e) {
        DB::rollBack();
        if ($roll_back instanceof Closure) {
            return $roll_back();
        }
        throw $e;
    }
}

/**
 * @return string
 */
function get_client_ip()
{
    $request = ApplicationContext::getContainer()->get(RequestInterface::class);
    $x_real_ip = $request->getHeader('x-real-ip');
    if (count($x_real_ip) == 1) {
        return (string)$x_real_ip[0];
    }
    $x_forwarded_for = $request->getHeader('x-forwarded-for');
    if (count($x_forwarded_for) == 1) {
        return (string)$x_forwarded_for[0];
    }
    return (string)Arr::get($request->getServerParams(), 'remote_addr', '127.0.0.1');
}
