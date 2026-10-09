<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Jam bawaan shift berubah: Pagi 08:30–16:30 → 08:00–14:00, Siang 12:00–21:00 → 14:00–20:00
     * (Malam 20:00–08:00 baru). Hanya master shift yang MASIH memakai jam bawaan lama yang ikut
     * diganti — shift yang jamnya sudah disesuaikan admin dibiarkan. Tidak menghitung ulang
     * presensi lama (rekap yang sudah tersimpan tetap seperti semula).
     */
    private const CHANGES = [
        'Pagi' => [['08:30:00', '16:30:00'], ['08:00:00', '14:00:00']],
        'Siang' => [['12:00:00', '21:00:00'], ['14:00:00', '20:00:00']],
    ];

    public function up(): void
    {
        foreach (self::CHANGES as $code => [[$oldStart, $oldEnd], [$newStart, $newEnd]]) {
            DB::table('shifts')
                ->where('code', $code)
                ->where('start_time', $oldStart)
                ->where('end_time', $oldEnd)
                ->update(['start_time' => $newStart, 'end_time' => $newEnd, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (self::CHANGES as $code => [[$oldStart, $oldEnd], [$newStart, $newEnd]]) {
            DB::table('shifts')
                ->where('code', $code)
                ->where('start_time', $newStart)
                ->where('end_time', $newEnd)
                ->update(['start_time' => $oldStart, 'end_time' => $oldEnd, 'updated_at' => now()]);
        }
    }
};
