<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            // Unit penempatan magang. Nullable supaya data lama & pendaftaran baru
            // (yang belum ditempatkan admin) tetap valid. institusi_id = asal sekolah/kampus,
            // tetap terpisah dari unit ini.
            $table->foreignId('unit_id')->nullable()->after('institusi_id')
                ->constrained('units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
