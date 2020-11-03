<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id 
 * @property string $queue 
 * @property string $payload queue class
 * @property string $failed_reason 
 * @property \Carbon\Carbon $created_at 
 */
class FailedJob extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'failed_jobs';
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