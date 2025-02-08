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
        Schema::create('admin_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 50)->nullable(false);
            $table->string('nama')->nullable();
            $table->text('file_path')->nullable();
            $table->string('kategori')->nullable();
            $table->unique(['nim', 'kategori']);

            $table->timestamps();
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_uploads');
    }
};
