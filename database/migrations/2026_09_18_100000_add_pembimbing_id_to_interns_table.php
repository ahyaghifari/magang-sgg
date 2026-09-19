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
        Schema::table('interns', function (Blueprint $table) {
            // Pembimbing yang ditugaskan mendampingi intern ini, terpisah dari mentor_id
            // (kolom sebelumnya) supaya Pembimbing dan Mentor bisa ditugaskan sebagai dua
            // orang yang berbeda untuk intern yang sama.
            $table->foreignId('pembimbing_id')->nullable()->after('unit_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pembimbing_id');
        });
    }
};
