<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMonthlyTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('monthly_totals', function (Blueprint $table) {
            $table->BigInteger('product_base_id')->unsigned();
            $table->foreign('product_base_id')->references('id')->on('product_bases');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('monthly_totals', function (Blueprint $table) {
            //
        });
    }
}
