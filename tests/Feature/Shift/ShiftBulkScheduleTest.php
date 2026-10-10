<?php

namespace Tests\Feature\Shift;

use App\Enums\UserRole;
use App\Livewire\Pembimbing\Shifts as PembimbingShifts;
use App\Livewire\Shifts\Index as InternShifts;
use App\Models\AttendanceRecord;
use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use App\Models\User;
use App\Notifications\ShiftChangeRequested;
use App\Services\Shift\ShiftAssignmentService;
use App\Services\Shift\ShiftChangeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Mode "Atur Per Tanggal": tiap tanggal diberi shift berbeda dan disimpan (Mentor) / diajukan
 * (intern) sekaligus. "Hari ini" = Senin 2026-10-05 (ShiftTestHelpers).
 */
class ShiftBulkScheduleTest extends TestCase
{
    use RefreshDatabase;
    use ShiftTestHelpers;

    private User $mentor;

    private Intern $intern;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShiftWorld();
        $this->withoutVite();
        Notification::fake();

        $this->mentor = $this->makeUser(UserRole::Mentor);
        $this->intern = $this->makeIntern(['mentor_id' => $this->mentor->id]);
        $this->intern->update(['uses_shift' => true]); // admin: memakai jadwal shift = Ya
    }

    private function service(): ShiftAssignmentService
    {
        return app(ShiftAssignmentService::class);
    }

    /** @param  array<string, string>  $map */
    private function entries(array $map): array
    {
        return collect($map)->map(fn ($shift, $date) => ['date' => $date, 'shift' => $shift])->values()->all();
    }

    private function assignment(string $date): ?InternShiftAssignment
    {
        return InternShiftAssignment::with('shift')->where('intern_id', $this->intern->id)->whereDate('date', $date)->first();
    }

    private function assertRejected(callable $fn): string
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            return (string) collect($e->errors())->flatten()->first();
        }
        $this->fail('Seharusnya seluruh batch ditolak.');
    }

    public function test_mentor_menyimpan_campuran_empat_jenis_untuk_tujuh_tanggal_dan_menghitung_ulang_lampau(): void
    {
        // 1 Okt sudah tercatat dengan jadwal tetap 08:00 → telat 50 menit.
        $this->syncTaps($this->intern, '2026-10-01', ['08:50:00', '17:00:00']);
        $this->assertSame(50, AttendanceRecord::where('nip', $this->intern->nip)->first()->late_minutes);

        $map = [
            '2026-10-01' => 'siang',  // lampau → presensi dihitung ulang (Siang 12:00 → tidak telat)
            '2026-10-02' => 'pagi',
            '2026-10-03' => 'libur',
            '2026-10-06' => 'malam',  // master Malam belum ada → dibuat otomatis
            '2026-10-07' => 'pagi',
            '2026-10-08' => 'siang',
            '2026-10-09' => 'libur',
        ];

        $result = $this->service()->applyEntries($this->mentor, $this->intern, $this->entries($map));

        $this->assertSame(array_keys($map), $result['saved']);
        $this->assertSame([], $result['skipped']);
        $this->assertSame(['2026-10-01', '2026-10-02', '2026-10-03'], $result['past_changed']);

        $expected = ['2026-10-01' => 'Siang', '2026-10-02' => 'Pagi', '2026-10-03' => 'Libur', '2026-10-06' => 'Malam',
            '2026-10-07' => 'Pagi', '2026-10-08' => 'Siang', '2026-10-09' => 'Libur'];
        foreach ($expected as $date => $type) {
            $row = $this->assignment($date);
            $this->assertSame($type, $row->off_day ? 'Libur' : $row->shift->code, $date);
            $this->assertSame($this->mentor->id, $row->updated_by, $date);
        }
        $this->assertTrue(Shift::where('company_id', $this->company->id)->where('code', 'Malam')->exists());

        $record = AttendanceRecord::where('nip', $this->intern->nip)->whereDate('date', '2026-10-01')->first();
        $this->assertSame(0, $record->late_minutes);
    }

    public function test_pilihan_yang_sama_dengan_jadwal_dilewati_dan_libur_tidak_menghapus_presensi(): void
    {
        $this->syncTaps($this->intern, '2026-10-02', ['08:00:00', '16:00:00']);
        $this->service()->apply($this->mentor, $this->intern, ['2026-10-07'], 'shift', $this->pagi->id);

        $result = $this->service()->applyEntries($this->mentor, $this->intern, $this->entries([
            '2026-10-02' => 'libur',
            '2026-10-07' => 'pagi',
        ]));

        $this->assertSame(['2026-10-02'], $result['saved']);
        $this->assertSame(['2026-10-07'], $result['skipped']);
        $this->assertSame(1, AttendanceRecord::where('nip', $this->intern->nip)->count());
    }

    public function test_mentor_di_luar_dampingan_ditolak_seluruh_batch(): void
    {
        $other = $this->makeUser(UserRole::Mentor);

        $this->assertRejected(fn () => $this->service()->applyEntries($other, $this->intern, $this->entries([
            '2026-10-06' => 'pagi', '2026-10-07' => 'siang',
        ])));

        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_pembimbing_admin_pimpinan_dan_intern_tidak_bisa_menyimpan_jadwal(): void
    {
        $pembimbing = $this->makeUser(UserRole::Pembimbing);
        $this->intern->update(['pembimbing_id' => $pembimbing->id]);

        foreach ([$pembimbing, $this->makeUser(UserRole::Admin), $this->makeUser(UserRole::Pimpinan), $this->intern->user] as $actor) {
            $this->assertRejected(fn () => $this->service()->applyEntries($actor, $this->intern, $this->entries(['2026-10-06' => 'pagi'])));
        }

        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_entri_tidak_valid_membatalkan_seluruh_batch(): void
    {
        $invalid = [
            'shift di luar 4 nilai' => [['date' => '2026-10-06', 'shift' => 'pagi'], ['date' => '2026-10-07', 'shift' => 'sore']],
            'tanggal dobel' => [['date' => '2026-10-06', 'shift' => 'pagi'], ['date' => '2026-10-06', 'shift' => 'siang']],
            'tanggal tidak ada' => [['date' => '2026-10-06', 'shift' => 'pagi'], ['date' => '2026-02-30', 'shift' => 'pagi']],
            'format salah' => [['date' => '06-10-2026', 'shift' => 'pagi']],
            'lebih dari 62' => collect(range(0, 62))->map(fn ($i) => ['date' => now()->addDays($i)->toDateString(), 'shift' => 'pagi'])->all(),
            'kosong' => [],
        ];

        foreach ($invalid as $label => $entries) {
            $this->assertRejected(fn () => $this->service()->applyEntries($this->mentor, $this->intern, $entries));
            $this->assertSame(0, InternShiftAssignment::count(), $label);
        }
    }

    public function test_livewire_mentor_menyimpan_per_tanggal_dan_pembimbing_ditolak(): void
    {
        Livewire::actingAs($this->mentor)
            ->test(PembimbingShifts::class, ['internId' => (string) $this->intern->id])
            ->set('selected', ['2026-10-06', '2026-10-07'])
            ->call('saveBulkShiftSchedule', $this->entries(['2026-10-06' => 'pagi', '2026-10-07' => 'libur']))
            ->assertDispatched('shift-toast', type: 'success', message: '2 tersimpan, 0 dilewati.')
            ->assertSet('selected', []);

        $this->assertSame(2, InternShiftAssignment::count());

        $pembimbing = $this->makeUser(UserRole::Pembimbing);
        $this->intern->update(['pembimbing_id' => $pembimbing->id]);

        Livewire::actingAs($pembimbing)
            ->test(PembimbingShifts::class, ['internId' => (string) $this->intern->id])
            ->assertSee('Hanya lihat')
            ->call('saveBulkShiftSchedule', $this->entries(['2026-10-08' => 'siang']))
            ->assertDispatched('shift-toast', type: 'error');

        $this->assertSame(2, InternShiftAssignment::count());
    }

    public function test_intern_tidak_bisa_menyimpan_jadwal_langsung(): void
    {
        $this->assertRejected(fn () => $this->service()->applyEntries($this->intern->user, $this->intern, $this->entries(['2026-10-06' => 'pagi'])));

        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_pengajuan_beberapa_tanggal_hanya_satu_notifikasi_gabungan(): void
    {
        $this->service()->apply($this->mentor, $this->intern, ['2026-10-07'], 'shift', $this->pagi->id);

        app(ShiftChangeRequestService::class)->submitEntries($this->intern->user, $this->intern, $this->entries([
            '2026-10-06' => 'pagi',
            '2026-10-07' => 'siang',
            '2026-10-08' => 'malam',
            '2026-10-09' => 'libur',
            '2026-10-10' => 'pagi',
        ]), 'Ada urusan keluarga');

        $this->assertSame(5, ShiftChangeRequest::count());
        Notification::assertSentToTimes($this->mentor, ShiftChangeRequested::class, 1);
        Notification::assertCount(1);

        Notification::assertSentTo($this->mentor, ShiftChangeRequested::class, function (ShiftChangeRequested $n) {
            $payload = $n->toWebPush($this->mentor, $n)->toArray();
            $body = explode("\n", $payload['body']);

            return $payload['title'] === '5 pengajuan perubahan shift dari ' . $this->intern->nama
                && count($body) === 5                              // 3 tanggal + "+2 lainnya" + alasan
                && str_contains($body[0], 'Kosong → Pagi')
                && str_contains($body[1], 'Pagi → Siang')
                && $body[3] === '+2 lainnya'
                && $body[4] === 'Alasan: Ada urusan keluarga';
        });
    }

    public function test_pengajuan_dengan_entri_tidak_valid_batal_seluruhnya(): void
    {
        $this->assertRejected(fn () => app(ShiftChangeRequestService::class)->submitEntries($this->intern->user, $this->intern, [
            ['date' => '2026-10-06', 'shift' => 'pagi'],
            ['date' => '2026-10-07', 'shift' => 'lembur'],
        ], 'Alasan'));

        $this->assertSame(0, ShiftChangeRequest::count());
        Notification::assertNothingSent();
    }

    public function test_panel_per_tanggal_tampil_sesuai_peran(): void
    {
        $this->service()->apply($this->mentor, $this->intern, ['2026-10-07'], 'shift', $this->pagi->id);

        // Mentor: toggle dua mode + preset rotasi + jam tiap pilihan; isian saat ini tersedia untuk badge.
        Livewire::actingAs($this->mentor)
            ->test(PembimbingShifts::class, ['internId' => (string) $this->intern->id])
            ->assertSee('Satu Shift untuk Semua')
            ->assertSee('Atur Per Tanggal')
            ->assertSee('Pagi → Siang → Malam')
            ->assertSee('20:00–08:00')
            ->assertSeeHtml('&quot;2026-10-07&quot;:&quot;Pagi&quot;');

        // Intern: hanya "Ajukan Perubahan" + Kirim Pengajuan dengan alasan; tanpa preset rotasi & tanpa Kosongkan.
        Livewire::actingAs($this->intern->user)
            ->test(InternShifts::class)
            ->assertSee('Ajukan Perubahan')
            ->assertSee('Kirim Pengajuan')
            ->assertSee('Isi semua dengan…')
            ->assertDontSee('Pagi → Siang → Malam')
            ->assertDontSee('Satu Shift untuk Semua')
            ->assertDontSee('Kosongkan');

        // Pembimbing: baca-saja, tidak ada panel sama sekali.
        $pembimbing = $this->makeUser(UserRole::Pembimbing);
        $this->intern->update(['pembimbing_id' => $pembimbing->id]);
        Livewire::actingAs($pembimbing)
            ->test(PembimbingShifts::class, ['internId' => (string) $this->intern->id])
            ->assertDontSee('Atur Per Tanggal');
    }

    public function test_livewire_intern_mengajukan_dan_ringkasan_dilewati(): void
    {
        Livewire::actingAs($this->intern->user)
            ->test(InternShifts::class)
            ->call('submitBulkShiftRequests', $this->entries([
                '2026-10-04' => 'pagi',   // lampau → dilewati
                '2026-10-06' => 'siang',
                '2026-11-20' => 'pagi',   // > 30 hari → dilewati
            ]), 'Kuliah')
            ->assertDispatched('shift-toast', type: 'success', message: '1 diajukan, 2 dilewati.');

        $this->assertSame(1, ShiftChangeRequest::count());
        $this->assertSame(0, InternShiftAssignment::count());
    }
}
