<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailysummarysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dailysummarys', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('asp_id')->unsigned();
            $table->foreign('asp_id')->references('id')->on('asps');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
            $table->integer('estimate_imp')->nullable();
            $table->integer('estimate_click')->nullable();
            $table->integer('estimate_cv')->nullable();
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
        Schema::dropIfExists('dailysummarys');
    }
}
