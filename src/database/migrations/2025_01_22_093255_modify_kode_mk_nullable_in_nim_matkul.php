<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyKodeMkNullableInNimMatkul extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nim_matkul', function (Blueprint $table) {
            $table->string('kode_mk')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('nim_matkul', function (Blueprint $table) {
            $table->string('kode_mk')->nullable(false)->change();
        });
    }
}
