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
            // nilai_akhir jadi rata-rata otomatis dari 4 aspek ini (lihat Intern::booted()) —
            // aspek ini yang diisi manual oleh pembimbing/mentor/admin, bukan nilai_akhir langsung.
            $table->unsignedTinyInteger('nilai_sikap')->nullable()->after('nilai_akhir');
            $table->unsignedTinyInteger('nilai_keterampilan')->nullable()->after('nilai_sikap');
            $table->unsignedTinyInteger('nilai_kedisiplinan')->nullable()->after('nilai_keterampilan');
            $table->unsignedTinyInteger('nilai_tanggung_jawab')->nullable()->after('nilai_kedisiplinan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->dropColumn(['nilai_sikap', 'nilai_keterampilan', 'nilai_kedisiplinan', 'nilai_tanggung_jawab']);
        });
    }
};
