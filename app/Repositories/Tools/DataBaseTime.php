<?php

declare(strict_types=1);

namespace App\Repositories\Tools;

use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Hyperf\DbConnection\Db;

class DataBaseTime
{
    /**
     * @param Blueprint $table
     * @param string $column
     * @return ColumnDefinition
     */
    static public function addUpdateTime(Blueprint $table, string $column = 'updated_at'): ColumnDefinition
    {
        return $table->dateTime($column)->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
    }

    /**
     * @param Blueprint $table
     * @param string $column
     * @return ColumnDefinition
     */
    static public function addCreateTime(Blueprint $table, string $column = 'created_at'): ColumnDefinition
    {
        return $table->dateTime($column)->useCurrent();
    }

    static public function addTimestamps(Blueprint $table, bool $is_update_index = false, bool $is_create_index = false)
    {
        if ($is_update_index) {
            self::addUpdateTime($table)->index();
        } else {
            self::addUpdateTime($table);
        }

        if ($is_create_index) {
            self::addCreateTime($table)->index();
        } else {
            self::addCreateTime($table);
        }
    }
}