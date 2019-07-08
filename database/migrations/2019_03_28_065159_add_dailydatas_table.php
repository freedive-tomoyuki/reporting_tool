<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDailydatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dailydatas', function (Blueprint $table) {
            $table->Integer('killed_flag')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dailydatas', function (Blueprint $table) {
            $table->Integer('killed_flag')->nullable(false)->change();

        });
    }
}
