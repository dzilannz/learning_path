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
        Schema::create('semester_mahasiswa', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('nim'); // Gunakan NIM sebagai pengenal mahasiswa
            $table->string('id_semester')->unique(); // ID semester dari API
            $table->foreignId('semester_id') // Foreign key ke tabel semesters
                  ->constrained('semester')
                  ->onDelete('cascade'); // Hapus data jika semester dihapus
            $table->integer('sks_diambil')->default(0); // SKS yang diambil
            $table->timestamps(); // Kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semester_mahasiswa'); // Menghapus tabel jika rollback
    }
};

