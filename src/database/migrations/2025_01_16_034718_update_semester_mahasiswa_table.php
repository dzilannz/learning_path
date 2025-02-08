<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('semester_mahasiswa', function (Blueprint $table) {
            // Hapus constraint unik pada id_semester
            $table->dropUnique(['id_semester']);

            // Tambahkan constraint unik pada kombinasi nim dan id_semester
            $table->unique(['nim', 'id_semester'], 'unique_nim_id_semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semester_mahasiswa', function (Blueprint $table) {
            // Hapus constraint unik pada kombinasi nim dan id_semester
            $table->dropUnique('unique_nim_id_semester');

            // Tambahkan kembali constraint unik pada id_semester
            $table->unique('id_semester');
        });
    }
};
