<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('kategori_id');
            $table->string('nama_menu', 100);
            $table->decimal('harga', 12, 2);
            $table->text('deskripsi')->nullable();
            $table->string('status_stok', 20)->default('tersedia');
            $table->boolean('apakah_laris')->default(false);

            $table->foreign('kategori_id')
                ->references('id')
                ->on('kategori')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};