<?php

namespace App\Services\Attendance;

use App\Models\Intern;
use App\Services\Shift\ScheduleResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Baca `access_logs` (DB HRIS, read-only), cocokkan NIP ke Intern -> perusahaan,
 * dan pasangkan scan menjadi entri harian. TIDAK menghitung apa pun.
 */
class AccessLogReader
{
    /** Semua akses access_logs lewat koneksi 'hris' (DB HRIS, read-only). */
    private function accessLogs()
    {
        return DB::connection('hris')->table('access_logs');
    }

    /**
     * @return array{
     *   entries: array<int, array{nip:string, company_id:mixed, date:string, check_in:?string, check_out:?string, single_scan:bool, devices:array<string>}>,
     *   unmatched: array<string,int>,
     *   scans: array<int, array{employee_id:string, intern_id:?int, nip:?string, matched:bool, scanned_at:string, scan_date:?string, scan_time:?string, device_name:?string}>,
     *   max_synced_at: ?string
     * }
     */
    /**
     * Berapa menit ke belakang dari watermark yang SELALU dibaca ulang tiap sync.
     * Mesin sidik jari sering mengirim tap ke HRIS terlambat / per paket (mis. jaringan mesin
     * putus sebentar), jadi tap dengan access_datetime LEBIH LAMA dari watermark bisa baru
     * muncul belakangan. Tanpa jendela ini tap seperti itu terlewat selamanya (jam masuk/pulang
     * kosong). Aman dibaca ulang: log tap di-upsert per (employee_id, scanned_at) dan
     * AttendanceRecordWriter menggabungkan waktu tap, jadi tidak ada data dobel.
     */
    public const LOOKBACK_MINUTES = 120;

    public function readIncremental(?string $sinceDatetime): array
    {
        $query = $this->accessLogs();

        if ($sinceDatetime) {
            $since = \Illuminate\Support\Carbon::parse($sinceDatetime)->subMinutes(self::LOOKBACK_MINUTES);
            $query->where('access_datetime', '>=', $since->toDateTimeString());
        }

        return $this->build($query->orderBy('access_datetime')->get());
    }

    /** Rentang tanggal, untuk backfill / jalankan ulang manual. */
    public function readRange(string $fromDate, string $toDate, ?string $nip = null): array
    {
        // +1 hari: tap pulang shift lintas hari di tanggal terakhir jatuh keesokan paginya.
        $toDate = \Illuminate\Support\Carbon::parse($toDate)->addDay()->toDateString();
        $query = $this->accessLogs()->whereBetween('access_date', [$fromDate, $toDate]);

        if ($nip) {
            $query->where('employee_id', $nip);
        }

        return $this->build($query->orderBy('access_datetime')->get());
    }

    private function build(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return ['entries' => [], 'unmatched' => [], 'scans' => [], 'max_synced_at' => null];
        }

        $maxSyncedAt = (string) $rows->max('access_datetime');

        // 1 query untuk semua NIP -> Intern -> company_id (lewat unit).
        $nips = $rows->pluck('employee_id')->filter()->unique()->values();

        $interns = Intern::query()
            ->whereIn('nip', $nips)
            ->with('unit:id,company_id')
            ->get(['id', 'nip', 'unit_id'])
            ->keyBy('nip');

        // Log mentah SETIAP tap — termasuk yang employee_id-nya tak cocok NIP mana pun.
        $scans = $rows->map(function ($r) use ($interns) {
            $intern = $interns->get($r->employee_id);

            return [
                'employee_id' => (string) $r->employee_id,
                'intern_id' => $intern?->id,
                'nip' => $intern?->nip,
                'matched' => $intern !== null,
                'scanned_at' => $r->access_datetime,
                'scan_date' => $r->access_date,
                'scan_time' => $r->access_time,
                'device_name' => $r->device_name ?? null,
            ];
        })->all();

        $entries = [];
        $unmatched = [];

        // Kelompokkan per (nip, tanggal KERJA). Biasanya = tanggal scan; tap pagi setelah shift
        // lintas hari (mis. Malam 20:00–08:00) masuk ke tanggal shift dimulai (ScheduleResolver::workDate).
        $resolver = new ScheduleResolver;
        $groups = $rows->groupBy(function ($r) use ($interns, $resolver) {
            $intern = $interns->get($r->employee_id);
            $date = $intern ? $resolver->workDate($intern, $r->access_date, (string) $r->access_time) : $r->access_date;

            return $r->employee_id.'|'.$date;
        });

        foreach ($groups as $key => $dayRows) {
            [$nip, $date] = explode('|', $key, 2);
            $intern = $interns->get($nip);

            if (! $intern) {
                $unmatched[$nip] = ($unmatched[$nip] ?? 0) + count($dayRows);
                continue;
            }

            $dayRows = $dayRows->sortBy('access_datetime')->values();
            $first = $dayRows->first()->access_time;
            $last = $dayRows->last()->access_time;

            $entries[] = [
                'nip' => $nip,
                'company_id' => $intern->unit?->company_id,
                'date' => $date,
                // Scan tunggal: keputusan check-in vs check-out final di calculator
                // (butuh end_time jadwal).
                'check_in' => $first,
                'check_out' => $dayRows->count() > 1 ? $last : null,
                'single_scan' => $dayRows->count() === 1,
                'devices' => $dayRows->pluck('device_name')->filter()->unique()->values()->all(),
            ];
        }

        return ['entries' => $entries, 'unmatched' => $unmatched, 'scans' => $scans, 'max_synced_at' => $maxSyncedAt];
    }
}
