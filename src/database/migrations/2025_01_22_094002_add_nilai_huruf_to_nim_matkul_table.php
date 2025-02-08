<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNilaiHurufToNimMatkulTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('nim_matkul', function (Blueprint $table) {
            $table->string('nilai_huruf')->nullable()->after('status'); // Menambahkan kolom nilai_huruf
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
            $table->dropColumn('nilai_huruf'); // Menghapus kolom nilai_huruf jika rollback
        });
    }
}
