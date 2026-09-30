<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ulasan', function (Blueprint $table) {
            $table->unsignedInteger('paket_id')->nullable()->after('menu_id');
        });
    }

    public function down(): void
    {
        Schema::table('ulasan', function (Blueprint $table) {
            $table->dropColumn('paket_id');
        });
    }
};
