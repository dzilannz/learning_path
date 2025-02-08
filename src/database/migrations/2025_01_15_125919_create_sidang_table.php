<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidang', function (Blueprint $table) {
            $table->id(); // Kolom ID sebagai primary key
            $table->string('nim', 50); // Kolom nim sebagai penghubung dengan API
            $table->boolean('seminar_kp')->default(false); // Status Seminar KP
            $table->boolean('sempro')->default(false); // Status Sempro
            $table->boolean('kolokium')->default(false); // Status Kolokium
            $table->boolean('kompre')->default(false); // Status Kompre
            $table->boolean('munaqasyah')->default(false); // Status Munaqasyah
            $table->timestamps(); // Kolom created_at dan updated_at
            $table->unique(['nim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidang'); // Menghapus tabel jika rollback
    }
};
