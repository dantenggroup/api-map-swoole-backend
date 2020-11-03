<?php

declare(strict_types=1);

namespace App\Repositories\Tools;

use Exception;
use Hyperf\Database\Query\Builder;
use Hyperf\Pool\Channel;
use Hyperf\Utils\Collection;
use Swoole\Coroutine\WaitGroup;

class BigDataScanHelper
{
    /**
     * @var Builder
    */
    private $builder;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var string
     */
    private $primary_key = 'id';

    /**
     * @var string|int
     */
    private $start_id = 0;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var callable
     */
    private $run_function;

    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var Channel
     */
    private $is_finished = false;

    /**
     * BigDataScanHelper constructor.
     * @param Builder $builder
     * @param int|string $start_id
     * @param int $limit
     */
    public function __construct(Builder $builder, $start_id = 0, int $limit = 1000)
    {
        $this->builder = $builder;
        $this->start_id = $start_id;
        $this->limit = $limit;
        $this->channel = new Channel(5);
    }

    /**
     * @param string $primary_key
     */
    public function setPrimaryKey(string $primary_key)
    {
        $this->primary_key = $primary_key;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns): void
    {
        $columns[] = $this->primary_key;
        $this->columns = collect($columns)->unique()->toArray();
    }

    public function setRunFunction(callable $callable)
    {
        $this->run_function = $callable;
    }

    public function begin()
    {
        $wg = new WaitGroup();
        $wg->add();
        go(function () {
            while(true) {
                try {
                    $data = $this->getNext();
                    if ($data->count() == 0) {
                        $this->is_finished = true;
                        break;
                    }
                    $this->start_id = $data->max($this->primary_key);
                    $this->channel->push($data);
                } catch (Exception $e) {
                    $this->is_finished = true;
                    throw $e;
                }
            }
        });
        go(function () use ($wg) {
            while(true) {
                if ($this->is_finished) {
                    $wg->done();
                    break;
                }
                $data = $this->channel->pop(-1);
                go(function () use ($data) {
                    call_user_func($this->run_function, $data);
                });
            }
        });
        $wg->wait();
    }

    /**
     * @return Collection
     */
    private function getNext()
    {
        return (clone $this->builder)->where($this->primary_key, '>', $this->start_id)->limit($this->limit)
                ->get($this->getColumns());
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        if ($this->columns) {
            return $this->columns;
        } else {
            return ['*'];
        }
    }
}