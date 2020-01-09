<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->String('product');
            $table->Integer('asp_id')->unsigned();
            $table->foreign('asp_id')->references('id')->on('asps');
            $table->BigInteger('product_base_id')->unsigned();
            $table->foreign('product_base_id')->references('id')->on('product_bases');
            $table->String('login_key');
            $table->String('login_value');
            $table->String('password_key');
            $table->String('password_value');
            $table->String('asp_product_id')->nullable();
            $table->String('asp_sponsor_id')->nullable();
            $table->String('product_order')->nullable();
            $table->Integer('price')->nullable()->default(0)->unsigned();
            $table->Integer('killed_flag')->default(0);
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
        Schema::dropIfExists('products');
    }
}
