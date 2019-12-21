<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonthlydatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthlydatas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('imp');
            $table->float('ctr');
            $table->integer('click');
            $table->float('cvr');
            $table->integer('cv');
            $table->integer('estimate_cv');
            $table->integer('active');
            $table->integer('partnership');
            $table->integer('price');
            $table->float('cpa')->nullable();
            $table->integer('cost')->nullable();
            $table->integer('asp_id');
            $table->integer('approval')->nullable();
            $table->integer('approval_price')->nullable();
            $table->float('approval_rate')->nullable();
            $table->date('date')->nullable();
            $table->integer('killed_flag')->default(0);
            $table->Integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
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
        Schema::dropIfExists('schedules');
    }
}

