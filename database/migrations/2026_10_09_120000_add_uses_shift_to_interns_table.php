<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin kini cukup memilih per intern "Memakai jadwal shift: Ya / Tidak" (form Intern),
     * menggantikan pemilihan intern per master shift. Hanya intern yang sebelumnya MEMANG dipilih
     * admin di form Master Shift (tabel intern_shift) yang otomatis diisi "Ya"; sekadar punya isian
     * jadwal tidak dihitung (bisa berupa data uji). Lainnya "Tidak" — admin bisa mengubahnya di form
     * Intern. Tabel lama intern_shift dibiarkan (tidak dipakai lagi), data tidak dihapus.
     */
    public function up(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->boolean('uses_shift')->default(false)->after('mentor_id');
        });

        DB::table('interns')
            ->whereIn('id', DB::table('intern_shift')->select('intern_id'))
            ->update(['uses_shift' => true]);
    }

    public function down(): void
    {
        Schema::table('interns', function (Blueprint $table) {
            $table->dropColumn('uses_shift');
        });
    }
};
