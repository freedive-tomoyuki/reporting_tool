<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyEstimateTotalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_estimate_total', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->integer('total_imp')->nullable();
            $table->integer('total_click')->nullable();
            $table->integer('total_cv')->nullable();
            $table->integer('estimate_total_imp')->nullable();
            $table->integer('estimate_total_click')->nullable();
            $table->integer('estimate_total_cv')->nullable();

            $table->date('date')->nullable();
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
        Schema::dropIfExists('daily_estimate_total');
    }
}
