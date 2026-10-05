<?php

namespace Tests\Feature\Shift;

use App\Models\AttendanceRecord;
use App\Models\InternShiftAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Perhitungan presensi memakai jadwal efektif (shift → Libur → jadwal tetap perusahaan). */
class ShiftAttendanceTest extends TestCase
{
    use RefreshDatabase;
    use ShiftTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShiftWorld();
    }

    private function assign($intern, string $date, $shift = null, bool $off = false): void
    {
        InternShiftAssignment::create([
            'intern_id' => $intern->id,
            'date' => $date,
            'shift_id' => $off ? null : $shift?->id,
            'off_day' => $off,
        ]);
    }

    private function record($intern, string $date): ?AttendanceRecord
    {
        return AttendanceRecord::where('nip', $intern->nip)->whereDate('date', $date)->first();
    }

    public function test_shift_pagi_tepat_waktu(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', $this->pagi);

        $this->syncTaps($intern, '2026-10-01', ['08:35:00', '16:40:00']);

        $r = $this->record($intern, '2026-10-01');
        $this->assertSame('present', $r->status);
        $this->assertSame(0, $r->late_minutes);          // toleransi 10 menit
        $this->assertSame(0, $r->early_leave_minutes);
        $this->assertSame(485 - 60, $r->working_minutes); // 08:35–16:40 dikurangi istirahat 60
    }

    public function test_shift_pagi_telat_dihitung_dari_jam_shift(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', $this->pagi);

        $this->syncTaps($intern, '2026-10-01', ['08:55:00', '16:30:00']);

        $r = $this->record($intern, '2026-10-01');
        $this->assertSame('late', $r->status);
        $this->assertSame(15, $r->late_minutes); // batas 08:40
    }

    public function test_shift_siang_pulang_cepat(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', $this->siang);

        $this->syncTaps($intern, '2026-10-01', ['11:50:00', '20:30:00']);

        $r = $this->record($intern, '2026-10-01');
        $this->assertSame('present', $r->status);  // pulang cepat tidak mengubah status
        $this->assertSame(0, $r->late_minutes);
        $this->assertSame(30, $r->early_leave_minutes);
    }

    public function test_tap_pulang_terlambat_masuk_lewat_sync_ulang_dan_idempoten(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', $this->siang);

        // Batch pertama hanya membawa tap masuk, tap pulang datang di batch berikutnya.
        $this->syncTaps($intern, '2026-10-01', ['12:20:00']);
        $this->syncTaps($intern, '2026-10-01', ['21:05:00']);
        // Sync ulang per jam membaca ulang tap yang sama — hasil tidak boleh berubah/dobel.
        $this->syncTaps($intern, '2026-10-01', ['12:20:00', '21:05:00']);
        $this->syncTaps($intern, '2026-10-01', ['12:20:00', '21:05:00']);

        $this->assertSame(1, AttendanceRecord::where('nip', $intern->nip)->count());
        $r = $this->record($intern, '2026-10-01');
        $this->assertSame('12:20:00', substr($r->check_in_time, 0, 8));
        $this->assertSame('21:05:00', substr($r->check_out_time, 0, 8));
        $this->assertSame(10, $r->late_minutes); // batas 12:10
        $this->assertSame(0, $r->early_leave_minutes);
    }

    public function test_libur_tidak_membuat_rekap_dan_tidak_alfa(): void
    {
        $intern = $this->makeIntern();
        $this->assign($intern, '2026-10-01', off: true);

        $this->syncTaps($intern, '2026-10-01', ['09:00:00', '15:00:00']);

        $this->assertNull($this->record($intern, '2026-10-01'));
        $this->assertSame(0, AttendanceRecord::where('status', 'absent')->count());
    }

    public function test_intern_tanpa_shift_tetap_memakai_jadwal_tetap_perusahaan(): void
    {
        $intern = $this->makeIntern();

        // Kamis 2026-10-01: jadwal tetap 08:00–16:00, toleransi 0.
        $this->syncTaps($intern, '2026-10-01', ['08:05:00', '15:50:00']);
        // Sabtu 2026-10-03: libur perusahaan → tidak ada rekap (perilaku lama).
        $this->syncTaps($intern, '2026-10-03', ['09:00:00', '12:00:00']);

        $r = $this->record($intern, '2026-10-01');
        $this->assertSame('late', $r->status);
        $this->assertSame(5, $r->late_minutes);
        $this->assertSame(10, $r->early_leave_minutes);
        $this->assertSame(405, $r->working_minutes);
        $this->assertNull($this->record($intern, '2026-10-03'));
    }

    public function test_tanggal_shift_yang_belum_diisi_kembali_ke_jadwal_tetap(): void
    {
        $intern = $this->makeIntern();
        $intern->shifts()->attach($this->pagi->id);       // terdaftar shift, tapi tanggal ini belum diisi
        $this->assign($intern, '2026-10-02', $this->pagi); // hanya tanggal lain yang diisi

        $this->syncTaps($intern, '2026-10-01', ['08:05:00', '16:00:00']);

        $this->assertSame(5, $this->record($intern, '2026-10-01')->late_minutes); // pakai 08:00, bukan 08:30
    }

    public function test_mengisi_jadwal_tidak_mengubah_presensi_yang_sudah_ada(): void
    {
        $pembimbing = $this->makeUser(\App\Enums\UserRole::Pembimbing);
        $intern = $this->makeIntern(['pembimbing_id' => $pembimbing->id]);
        $this->syncTaps($intern, '2026-10-01', ['08:05:00', '15:50:00']);
        $before = $this->record($intern, '2026-10-01')->only(['check_in_time', 'check_out_time', 'late_minutes', 'early_leave_minutes', 'working_minutes', 'status']);

        // Pembimbing mengisi jadwal untuk tanggal ke depan → presensi lama tidak tersentuh.
        app(\App\Services\Shift\ShiftAssignmentService::class)
            ->apply($pembimbing, $intern, ['2026-10-06', '2026-10-07'], 'off');

        $this->assertSame($before, $this->record($intern, '2026-10-01')->only(array_keys($before)));
    }
}
