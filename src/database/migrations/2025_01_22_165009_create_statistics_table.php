<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('statistics', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name')->unique(); // Nama statistik (contoh: ibtitah_count)
            $table->integer('value')->default(0); // Nilai statistik
            $table->timestamp('updated_at')->nullable(); // Waktu terakhir diperbarui
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('statistics');
    }
};
