<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // pembimbing pemberi nilai
            $table->unsignedTinyInteger('rating');                          // 1..5 bintang
            $table->timestamps();

            // Satu pembimbing hanya punya satu penilaian per jurnal (rata-rata dihitung antar-pembimbing).
            $table->unique(['journal_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_reviews');
    }
};
