<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailydataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dailydata', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('imp');
            $table->Integer('click');
            $table->Integer('cv');
            $table->Integer('cvr');
            $table->Integer('ctr');
            $table->Integer('active');
            $table->Integer('partnership');
            $table->Integer('asp_id');
            $table->Integer('product_id');
            $table->Integer('killed_flag');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dailydata');
    }
}
