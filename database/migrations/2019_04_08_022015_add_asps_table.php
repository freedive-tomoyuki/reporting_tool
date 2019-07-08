<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAspsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asps', function (Blueprint $table) {
            $table->String('login_url')->nullable();
            $table->String('login_selector')->nullable();

            $table->String('daily_imp_selector')->nullable();
            $table->String('daily_imp_url')->nullable(); // URL that get imp 

            $table->String('daily_click_selector')->nullable();
            $table->String('daily_click_url')->nullable();// URL that get click
            
            $table->String('daily_cv_selector')->nullable();
            $table->String('daily_cv_url')->nullable(); // URL that get cv
            
            $table->String('daily_active_selector')->nullable(); 
            $table->String('daily_active_url')->nullable(); // URL that get active 
            
            $table->String('daily_partnership_selector')->nullable();
            $table->String('daily_partnership_url')->nullable(); // URL that get partnership 

            $table->String('lp1_url')->nullable();//firstpage after login
            $table->String('lp2_url')->nullable();
            $table->String('lp3_url')->nullable();
            $table->String('lp4_url')->nullable();
            $table->String('lp5_url')->nullable();
            $table->String('lp6_url')->nullable();
            $table->String('lp7_url')->nullable();
            $table->String('lp8_url')->nullable();
            $table->String('lp9_url')->nullable();

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
