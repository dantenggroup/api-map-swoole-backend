<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id 
 * @property string $queue 
 * @property string $payload queue class
 * @property int $attempts number of attempts
 * @property string $available_at 
 * @property \Carbon\Carbon $created_at 
 */
class Job extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'jobs';
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
    protected $casts = ['id' => 'integer', 'attempts' => 'integer', 'created_at' => 'datetime'];
}