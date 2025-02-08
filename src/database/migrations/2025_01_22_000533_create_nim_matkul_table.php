<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNimMatkulTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nim_matkul', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('nim'); // NIM mahasiswa
            $table->string('kode_mk'); // Kode mata kuliah
            $table->string('semester_id'); // Semester ID (misalnya 20241)
            $table->string('status')->default('tidak aktif'); // Status mahasiswa (aktif/tidak aktif)
            $table->timestamps(); // created_at dan updated_at

            // Tambahkan index jika diperlukan
            $table->foreign('nim')->references('nim')->on('nims')->onDelete('cascade'); // Foreign key ke tabel nims
            $table->index(['nim', 'kode_mk']); // Index untuk query lebih cepat
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nim_matkul');
    }
}

