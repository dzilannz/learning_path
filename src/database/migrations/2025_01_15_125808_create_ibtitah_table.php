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
        Schema::create('ibtitah', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 50)->nullable(false);
            $table->text('file_path')->nullable();
            $table->string('kategori')->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('proof_file')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('file_diupload_admin')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unique(['nim', 'kategori']);

            // Kolom baru
            

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('ibtitah');
    }
};
