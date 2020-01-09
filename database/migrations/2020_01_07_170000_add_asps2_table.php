<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAsps2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asps', function (Blueprint $table) {
            $table->String('login_key')->after('login_url')->nullable();
            $table->String('password_key')->after('login_key')->nullable();

            $table->String('daily_price_selector')->after('daily_partnership_url')->nullable();
            $table->String('daily_price_url')->after('daily_price_selector')->nullable();// URL that get click
            
            $table->integer('sponsor_id_require_flag')->after('daily_price_url')->nullable();
            $table->integer('product_id_require_flag')->after('sponsor_id_require_flag')->nullable(); // URL that get cv



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asps', function (Blueprint $table) {
            //
        });
    }
}
