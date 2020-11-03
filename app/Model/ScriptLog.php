<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id 
 * @property string $uuid 运行中日志id
 * @property string $name 脚本名
 * @property string $description 脚本说明
 * @property string $class_name 脚本类名
 * @property int $run_time_ms 运行时间（毫秒）
 * @property int $type 运行状态
 * @property string $result 运行结果
 * @property string $run_as 脚本运行数据库用户名
 * @property \Carbon\Carbon $updated_at 
 * @property \Carbon\Carbon $created_at 
 */
class ScriptLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'script_logs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'run_time_ms' => 'integer', 'type' => 'integer', 'updated_at' => 'datetime', 'created_at' => 'datetime'];
}