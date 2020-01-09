<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDailyDiffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_diffs', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('imp');
            $table->float('ctr');
            $table->Integer('click');
            $table->float('cvr');
            $table->Integer('cv');
            $table->Integer('active')->default(0);
            $table->Integer('partnership')->default(0);
            $table->Integer('price')->nullable();
            $table->float('cpa')->nullable();
            $table->Integer('cost');
            $table->date('date')->nullable();
            $table->Integer('asp_id');
            $table->Integer('product_id');
            $table->Integer('estimate_cv')->nullable();
            $table->Integer('killed_flag')->nullable()->default(0);
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
