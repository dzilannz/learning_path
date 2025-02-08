<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menambahkan kolom pada tabel 'sidang'
        Schema::table('sidang', function (Blueprint $table) {
            // Menambahkan kolom angkatan
            $table->unsignedSmallInteger('angkatan')->nullable(); 

            // Menambahkan kolom total_sidang
            $table->unsignedSmallInteger('total_sidang')->default(0);

            // Menambahkan index pada kolom 'angkatan' dan 'nim'
            $table->index(['nim', 'angkatan']);

            // Menambahkan relasi dengan tabel nims pada kolom nim
            $table->foreign('nim')->references('nim')->on('nims')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Menghapus kolom yang ditambahkan
        Schema::table('sidang', function (Blueprint $table) {
            $table->dropColumn(['angkatan', 'total_sidang']);
            $table->dropForeign(['nim']);
        });
    }
};
