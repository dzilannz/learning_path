<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNamaMkToNimMatkulTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nim_matkul', function (Blueprint $table) {
            $table->string('nama_mk')->nullable()->after('kode_mk'); // Tambahkan kolom nama_mk
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
            $table->dropColumn('nama_mk'); // Hapus kolom nama_mk jika rollback
        });
    }
}
