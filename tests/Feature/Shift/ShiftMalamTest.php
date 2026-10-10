<?php

namespace Tests\Feature\Shift;

use App\Enums\UserRole;
use App\Models\AccessScanLog;
use App\Models\AttendanceRecord;
use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Models\Shift;
use App\Services\Attendance\AccessLogReader;
use App\Services\Attendance\AttendanceRecordWriter;
use App\Services\Attendance\FixedScheduleCalculator;
use App\Services\Shift\ScheduleResolver;
use App\Services\Shift\ShiftAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Shift Malam 20:00–08:00 (lintas hari): tap pulang esok pagi dihitung ke tanggal shift dimulai.
 * "Hari ini" = Senin 2026-10-05 (ShiftTestHelpers).
 */
class ShiftMalamTest extends TestCase
{
    use RefreshDatabase;
    use ShiftTestHelpers;

    private Shift $malam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShiftWorld();
        $this->malam = Shift::factory()->malam()->create(['company_id' => $this->company->id, 'late_tolerance_minutes' => 0]);
    }

    private function assign(Intern $intern, string $date, Shift $shift): void
    {
        InternShiftAssignment::create(['intern_id' => $intern->id, 'date' => $date, 'shift_id' => $shift->id, 'off_day' => false]);
    }

    /**
     * Jalankan jalur sync HRIS yang asli (AccessLogReader::build → calculator → writer) untuk
     * daftar tap ['Y-m-d H:i:s', ...]. $perBatch = true meniru sync incremental (1 tap per batch).
     */
    private function syncFromHris(Intern $intern, array $datetimes, bool $perBatch = false): void
    {
        $rows = collect($datetimes)->map(fn ($dt) => (object) [
            'employee_id' => $intern->nip,
            'access_datetime' => $dt,
            'access_date' => substr($dt, 0, 10),
            'access_time' => substr($dt, 11, 8),
            'device_name' => 'Gerbang',
        ]);

        $build = (new \ReflectionClass(AccessLogReader::class))->getMethod('build');
        $batches = $perBatch ? $rows->map(fn ($r) => collect([$r])) : collect([$rows]);

        foreach ($batches as $batch) {
            $calculator = new FixedScheduleCalculator(new ScheduleResolver);
            $writer = new AttendanceRecordWriter($calculator);
            foreach ($build->invoke(new AccessLogReader, $batch)['entries'] as $entry) {
                if ($calc = $calculator->calculate($entry)) {
                    $writer->save($calc);
                }
            }
        }
    }

    private function record(Intern $intern, string $date): ?AttendanceRecord
    {
        return AttendanceRecord::where('nip', $intern->nip)->whereDate('date', $date)->first();
    }

    public function test_shift_malam_ditandai_lintas_hari(): void
    {
        $this->assertTrue($this->malam->is_overnight);
        $this->assertFalse($this->pagi->is_overnight);
        $this->assertSame(12 * 60, $this->malam->workingMinutes());
        $this->assertSame(['start' => '20:00', 'end' => '08:00'], Shift::DEFAULT_TIMES['Malam']);
    }

    public function test_masuk_malam_dan_pulang_esok_pagi_jadi_satu_rekap(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', $this->malam);

        $this->syncFromHris($intern, ['2026-10-01 19:55:00', '2026-10-02 08:05:00']);

        $row = $this->record($intern, '2026-10-01');
        $this->assertSame('19:55:00', substr($row->check_in_time, 0, 8));
        $this->assertSame('08:05:00', substr($row->check_out_time, 0, 8));
        $this->assertSame(0, $row->late_minutes);
        $this->assertSame(0, $row->early_leave_minutes);
        $this->assertSame(730, $row->working_minutes); // 19:55 → 08:05
        $this->assertSame('present', $row->status);
        $this->assertNull($this->record($intern, '2026-10-02'), 'Tap pulang tidak boleh jadi rekap hari berikutnya');
    }

    public function test_sync_per_tap_tetap_tergabung_dan_telat_pulang_cepat_dihitung(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', $this->malam);

        $this->syncFromHris($intern, ['2026-10-01 20:15:00', '2026-10-02 07:30:00'], perBatch: true);

        $row = $this->record($intern, '2026-10-01');
        $this->assertSame('20:15:00', substr($row->check_in_time, 0, 8));
        $this->assertSame('07:30:00', substr($row->check_out_time, 0, 8));
        $this->assertSame(15, $row->late_minutes);
        $this->assertSame(30, $row->early_leave_minutes);
        $this->assertSame('late', $row->status);
        $this->assertNull($this->record($intern, '2026-10-02'));
    }

    public function test_tap_pulang_saja_tercatat_sebagai_jam_pulang(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', $this->malam);

        $this->syncFromHris($intern, ['2026-10-02 08:03:00']);

        $row = $this->record($intern, '2026-10-01');
        $this->assertNull($row->check_in_time);
        $this->assertSame('08:03:00', substr($row->check_out_time, 0, 8));
    }

    public function test_malam_lalu_siang_tap_masuk_siang_tidak_terbawa_ke_malam(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', $this->malam);
        $this->assign($intern, '2026-10-02', $this->siang); // 12:00–21:00

        $this->syncFromHris($intern, ['2026-10-01 19:58:00', '2026-10-02 08:01:00', '2026-10-02 11:50:00', '2026-10-02 21:05:00']);

        $this->assertSame('08:01:00', substr($this->record($intern, '2026-10-01')->check_out_time, 0, 8));
        $siang = $this->record($intern, '2026-10-02');
        $this->assertSame('11:50:00', substr($siang->check_in_time, 0, 8));
        $this->assertSame('21:05:00', substr($siang->check_out_time, 0, 8));
    }

    public function test_intern_tanpa_shift_malam_tidak_berubah(): void
    {
        $intern = $this->makeIntern();

        $this->syncFromHris($intern, ['2026-10-01 07:55:00', '2026-10-01 16:10:00', '2026-10-02 08:05:00']);

        $this->assertSame('16:10:00', substr($this->record($intern, '2026-10-01')->check_out_time, 0, 8));
        $this->assertSame('08:05:00', substr($this->record($intern, '2026-10-02')->check_in_time, 0, 8));
    }

    public function test_koreksi_jadi_malam_memindahkan_tap_pagi_dari_hari_berikutnya(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);

        foreach (['2026-10-01 19:55:00', '2026-10-02 08:05:00'] as $dt) {
            AccessScanLog::create([
                'employee_id' => $intern->nip, 'intern_id' => $intern->id, 'nip' => $intern->nip, 'matched' => true,
                'scanned_at' => $dt, 'scan_date' => substr($dt, 0, 10), 'scan_time' => substr($dt, 11, 8),
            ]);
        }

        // Sebelum dikoreksi: jadwal tetap → dua rekap terpisah.
        Artisan::call('attendance:rebuild', ['--nip' => $intern->nip]);
        $this->assertNotNull($this->record($intern, '2026-10-02'));

        app(ShiftAssignmentService::class)->apply($mentor, $intern, ['2026-10-01'], 'shift', $this->malam->id);

        $row = $this->record($intern, '2026-10-01');
        $this->assertSame('19:55:00', substr($row->check_in_time, 0, 8));
        $this->assertSame('08:05:00', substr($row->check_out_time, 0, 8));
        $this->assertSame(0, $row->late_minutes);
        $this->assertNull($this->record($intern, '2026-10-02'), 'Rekap usang hari berikutnya dihapus');

        // Rebuild ulang hasilnya sama.
        Artisan::call('attendance:rebuild', ['--nip' => $intern->nip]);
        $this->assertSame('08:05:00', substr($this->record($intern, '2026-10-01')->check_out_time, 0, 8));
        $this->assertNull($this->record($intern, '2026-10-02'));
    }
}
