<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logbook_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('logbook_id')
                ->constrained('logbooks')
                ->cascadeOnDelete();

            $table->foreignId('dosen_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('status');
            $table->text('komentar')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbook_reviews');
    }
};
