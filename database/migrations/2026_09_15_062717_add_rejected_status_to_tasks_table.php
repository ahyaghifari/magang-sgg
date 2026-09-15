<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambah nilai enum 'rejected' — dipakai saat intern menolak tugas (belum bisa
        // mengerjakan / ada urusan lain). Enum di-modify lewat raw SQL karena Schema::change()
        // butuh doctrine/dbal yang tidak dipasang di proyek ini.
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('pending', 'in_progress', 'done', 'rejected') NOT NULL DEFAULT 'pending'");

        Schema::table('tasks', function (Blueprint $table) {
            // Alasan singkat dari intern saat menolak tugas.
            $table->string('rejection_reason')->nullable()->after('completion_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        DB::statement("UPDATE tasks SET status = 'pending' WHERE status = 'rejected'");
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('pending', 'in_progress', 'done') NOT NULL DEFAULT 'pending'");
    }
};
