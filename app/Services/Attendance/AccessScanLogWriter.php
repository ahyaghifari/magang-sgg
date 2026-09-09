<?php

namespace App\Services\Attendance;

use App\Models\AccessScanLog;
use Illuminate\Support\Carbon;

/**
 * Simpan log mentah tiap tap sidik jari ke `access_scan_logs`.
 * Upsert dedupe berdasarkan (employee_id, scanned_at) supaya sync berulang aman.
 */
class AccessScanLogWriter
{
    /**
     * @param  array<int, array{employee_id:string, intern_id:?int, nip:?string, matched:bool, scanned_at:string, scan_date:?string, scan_time:?string, device_name:?string}>  $scans
     * @return int  jumlah baris yang diproses
     */
    public function saveMany(array $scans): int
    {
        if ($scans === []) {
            return 0;
        }

        $now = Carbon::now();

        $rows = array_map(fn (array $s): array => [
            'employee_id' => $s['employee_id'],
            'intern_id' => $s['intern_id'] ?? null,
            'nip' => $s['nip'] ?? null,
            'matched' => $s['matched'] ?? false,
            'scanned_at' => $s['scanned_at'],
            'scan_date' => $s['scan_date'] ?? null,
            'scan_time' => $s['scan_time'] ?? null,
            'device_name' => $s['device_name'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $scans);

        foreach (array_chunk($rows, 500) as $chunk) {
            AccessScanLog::upsert(
                $chunk,
                ['employee_id', 'scanned_at'],
                ['intern_id', 'nip', 'matched', 'scan_date', 'scan_time', 'device_name', 'updated_at'],
            );
        }

        return count($rows);
    }
}
