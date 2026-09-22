<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti rubrik penilaian sederhana (4 aspek, skala 0-100) dengan form penilaian
     * resmi perusahaan "Appraisal on the Job Training Result" — 10 kriteria (skala
     * 1-4), terbagi 2 kategori (Attitude, Knowledge & Skill). nilai_akhir jadi
     * rata-rata 10 kriteria ini (skala 1.00-4.00), predikat pakai istilah form
     * (Excellent/Good/Fair/Below Average/Poor).
     */
    public function up(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            // nilai_akhir juga di-drop & dibuat ulang (bukan ->change()) supaya tidak perlu
            // doctrine/dbal — tipenya berubah dari integer 0-100 jadi decimal 1.00-4.00.
            $table->dropColumn(['nilai_sikap', 'nilai_keterampilan', 'nilai_kedisiplinan', 'nilai_tanggung_jawab', 'nilai_akhir']);
        });

        Schema::table('interns', function (Blueprint $table) {
            $table->decimal('nilai_akhir', 3, 2)->nullable()->after('tanggal_selesai');

            // ATTITUDE
            $table->decimal('nilai_performance', 3, 2)->nullable()->after('nilai_akhir');
            $table->decimal('nilai_motivation', 3, 2)->nullable()->after('nilai_performance');
            $table->decimal('nilai_responsibility', 3, 2)->nullable()->after('nilai_motivation');
            $table->decimal('nilai_cooperativeness', 3, 2)->nullable()->after('nilai_responsibility');
            $table->decimal('nilai_attendance', 3, 2)->nullable()->after('nilai_cooperativeness');
            // KNOWLEDGE & SKILL
            $table->decimal('nilai_job_knowledge', 3, 2)->nullable()->after('nilai_attendance');
            $table->decimal('nilai_quality_of_work', 3, 2)->nullable()->after('nilai_job_knowledge');
            $table->decimal('nilai_job_speed', 3, 2)->nullable()->after('nilai_quality_of_work');
            $table->decimal('nilai_initiative', 3, 2)->nullable()->after('nilai_job_speed');
            $table->decimal('nilai_improvement', 3, 2)->nullable()->after('nilai_initiative');
        });
    }

    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->dropColumn([
                'nilai_akhir', 'nilai_performance', 'nilai_motivation', 'nilai_responsibility',
                'nilai_cooperativeness', 'nilai_attendance', 'nilai_job_knowledge',
                'nilai_quality_of_work', 'nilai_job_speed', 'nilai_initiative', 'nilai_improvement',
            ]);
        });

        Schema::table('interns', function (Blueprint $table) {
            $table->unsignedTinyInteger('nilai_akhir')->nullable()->after('tanggal_selesai');
            $table->unsignedTinyInteger('nilai_sikap')->nullable();
            $table->unsignedTinyInteger('nilai_keterampilan')->nullable();
            $table->unsignedTinyInteger('nilai_kedisiplinan')->nullable();
            $table->unsignedTinyInteger('nilai_tanggung_jawab')->nullable();
        });
    }
};
