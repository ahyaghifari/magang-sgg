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
        Schema::table('users', function (Blueprint $table) {
            // Perusahaan tempat pembimbing/mentor/pimpinan bertugas — sekadar info profil,
            // TIDAK membatasi data yang bisa mereka lihat/kelola di portal (unit_id sudah ada
            // untuk penempatan; kolom ini terpisah karena pembimbing/mentor/pimpinan sering
            // mengawasi lintas unit dalam satu perusahaan, bukan cuma satu unit).
            $table->foreignId('company_id')->nullable()->after('unit_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
