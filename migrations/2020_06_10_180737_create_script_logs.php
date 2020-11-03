<?php

use App\Repositories\Tools\DataBaseTime;
use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateScriptLogs extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('script_logs')) {
            Schema::create('script_logs', function (Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->string('uuid')->comment('运行中日志id');
                $table->string('name')->index()->comment('脚本名');
                $table->string('description')->comment('脚本说明');
                $table->string('class_name')->comment('脚本类名');
                $table->integer('run_time_ms')->comment('运行时间（毫秒）');
                $table->tinyInteger('type')->comment('运行状态');
                $table->text('result')->comment('运行结果');
                $table->string('run_as')->comment('脚本运行数据库用户名');
                DataBaseTime::addTimestamps($table);
            });
        }

        if (!Schema::hasTable('script_sub_logs')) {
            Schema::create('script_sub_logs', function (Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->string('uuid')->index()->comment('主脚本ID');
                $table->string('message');
                $table->text('context');
                DataBaseTime::addCreateTime($table);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
}
