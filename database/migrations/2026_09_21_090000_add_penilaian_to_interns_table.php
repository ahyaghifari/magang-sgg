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
            // Penilaian akhir dari Pembimbing/Mentor (atau admin) — nilai 0-100, predikat
            // dihitung otomatis dari nilai (lihat Intern::predikat()), tidak disimpan terpisah
            // supaya selalu konsisten.
            $table->unsignedTinyInteger('nilai_akhir')->nullable()->after('tanggal_selesai');
            $table->text('catatan_penilaian')->nullable()->after('nilai_akhir');
            $table->foreignId('dinilai_oleh')->nullable()->after('catatan_penilaian')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('dinilai_pada')->nullable()->after('dinilai_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dinilai_oleh');
            $table->dropColumn(['nilai_akhir', 'catatan_penilaian', 'dinilai_pada']);
        });
    }
};
