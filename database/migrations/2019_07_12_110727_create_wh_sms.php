<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWhSms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('xgwh_online')->create('wh_sms', function (Blueprint $table){
            $table->increments('id');
            $table->integer('touid');
            $table->char('phone');
            $table->text('content');
            $table->int('addtime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('xgwh_online')->dropIfExists('wh_sms');
    }
}
