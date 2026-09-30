<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu', function (Blueprint $table) {
            $table->string('gambar', 255)->nullable()->after('deskripsi');
        });

        Schema::table('paket_catering', function (Blueprint $table) {
            $table->string('gambar', 255)->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('menu', function (Blueprint $table) {
            $table->dropColumn('gambar');
        });

        Schema::table('paket_catering', function (Blueprint $table) {
            $table->dropColumn('gambar');
        });
    }
};
