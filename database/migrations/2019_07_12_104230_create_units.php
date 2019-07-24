<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id');
            $table->string('name');
            $table->integer('level');
            $table->integer('sort');
            $table->tinyInteger('dis');
            $table->tinyInteger('type');
            $table->tinyInteger('area');
            $table->string('z_score');
            $table->string('x_score');
            $table->string('y_score');
            $table->string('area');
            $table->string('code');
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
        Schema::dropIfExists('units');
    }
}
