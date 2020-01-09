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
            $table->BigInteger('product_base_id')->after('asp_id')->unsigned();
            $table->foreign('product_base_id')->references('id')->on('product_bases');
            $table->String('asp_product_id')->after('password_value')->nullable();
            $table->String('asp_sponsor_id')->after('asp_product_id')->nullable();
            $table->String('product_order')->after('asp_sponsor_id')->nullable();
            $table->Integer('price')->after('product_order')->nullable()->default(0)->unsigned();

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
