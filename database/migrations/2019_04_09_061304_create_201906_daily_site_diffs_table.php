<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Create201906DailySiteDiffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        Schema::create('201906_daily_site_diffs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('media_id');
            $table->string('site_name');
            $table->Integer('imp');
            $table->Integer('click');
            $table->Integer('cv');
            $table->Integer('estimate_cv')->nullable();
            $table->float('cvr')->nullable();
            $table->float('ctr')->nullable();
            $table->float('cpa')->nullable();
            $table->Integer('cost')->nullable();
            $table->Integer('price')->nullable();

            $table->string('url')->nullable();
            $table->date('date');
            $table->Integer('killed_flag')->default('0');
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
        Schema::dropIfExists('201906_daily_site_diffs');
    }
}
