<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoginLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('login_time');
            $table->text('http_user_agent');
            $table->string('login_address');
            $table->string('user_name');
            $table->string('device');
            $table->string('browser');
            $table->string('platform');
            $table->string('language');
            $table->integer('area_id');
            $table->string('area');
            $table->integer('units_id');
            $table->string('units');
            $table->string('phone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_log');
    }
}
