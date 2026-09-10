<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Jaga-jaga bila ada baris lama bertipe 'cuti' — alihkan ke 'izin' sebelum enum dipersempit.
        DB::table('leave_requests')->where('type', 'cuti')->update(['type' => 'izin']);

        DB::statement("ALTER TABLE leave_requests MODIFY type ENUM('izin', 'sakit') NOT NULL DEFAULT 'izin'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE leave_requests MODIFY type ENUM('izin', 'sakit', 'cuti') NOT NULL DEFAULT 'izin'");
    }
};
