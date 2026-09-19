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
            // Mentor/pembimbing yang ditugaskan mendampingi intern ini secara khusus.
            // Nullable: penugasan mentor perorangan bersifat opsional, terpisah dari
            // unit_id (penempatan kerja) dan dari daftar pembimbing/mentor umum yang
            // sudah bisa meninjau semua intern lewat /izin-intern dkk.
            $table->foreignId('mentor_id')->nullable()->after('unit_id')
                ->constrained('users')->nullOnDelete();

            $table->date('tanggal_mulai')->nullable()->after('jenis_kelamin');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mentor_id');
            $table->dropColumn(['tanggal_mulai', 'tanggal_selesai']);
        });
    }
};
