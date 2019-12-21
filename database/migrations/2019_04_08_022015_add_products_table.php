<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->BigInteger('product_base_id')->unsigned();
            $table->foreign('product_base_id')->references('id')->on('product_bases');
            $table->Integer('asp_product_id')->after('password_value')->nullable();
            $table->Integer('asp_sponsor_id')->after('password_value')->nullable();
            $table->Integer('price')->nullable()->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
 
    }
}
