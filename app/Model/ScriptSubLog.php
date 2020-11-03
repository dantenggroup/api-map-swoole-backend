<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id 
 * @property string $uuid 主脚本ID
 * @property string $message 
 * @property string $context 
 * @property \Carbon\Carbon $created_at 
 */
class ScriptSubLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'script_sub_logs';
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
    protected $casts = ['id' => 'integer', 'created_at' => 'datetime'];
}