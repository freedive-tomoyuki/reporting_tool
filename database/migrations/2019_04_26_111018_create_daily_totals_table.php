<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_totals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->integer('total_imp')->nullable();
            $table->integer('total_click')->nullable();
            $table->integer('total_cv')->nullable();
            $table->float('total_cvr')->nullable();
            $table->float('total_ctr')->nullable();
            $table->float('total_cost')->nullable();
            $table->integer('total_price')->nullable();
            $table->integer('total_cpa')->nullable();
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
        Schema::dropIfExists('daily_totals');
    }
}
