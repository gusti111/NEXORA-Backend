<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkls', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mahasiswa_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('dosen_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('korlap_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('nama_instansi');
            $table->text('alamat_instansi')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();

            $table->string('status')->default('berlangsung');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pkls');
    }
};
