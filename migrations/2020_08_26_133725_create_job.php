<?php

use App\Repositories\Tools\DataBaseTime;
use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateJob extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->string('queue')->index();
                $table->longText('payload')->comment('queue class');
                $table->unsignedTinyInteger('attempts')->default(0)->comment('number of attempts');
                DataBaseTime::addCreateTime($table, 'available_at')->index();
                DataBaseTime::addCreateTime($table);
            });
        }
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->string('queue');
                $table->longText('payload')->comment('queue class');
                $table->longText('failed_reason');
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
