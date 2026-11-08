<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            // nullable() penting biar data logbook lama yang belum punya judul tetap aman
            $table->string('judul_kegiatan')->nullable()->after('tanggal_kegiatan');
        });
    }

    public function down(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropColumn('judul_kegiatan');
        });
    }
};