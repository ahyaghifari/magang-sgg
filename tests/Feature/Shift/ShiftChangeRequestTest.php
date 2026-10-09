<?php

namespace Tests\Feature\Shift;

use App\Enums\UserRole;
use App\Livewire\Pembimbing\Shifts as PembimbingShifts;
use App\Livewire\Shifts\Index as InternShifts;
use App\Models\AccessScanLog;
use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use App\Models\User;
use App\Notifications\ShiftChangeDecided;
use App\Notifications\ShiftChangeRequested;
use App\Services\Shift\ShiftAssignmentService;
use App\Services\Shift\ShiftChangeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Intern hanya MENGAJUKAN perubahan jadwal shift (tidak pernah mengubah langsung); pengajuan
 * diputuskan Mentor-nya. "Hari ini" = Senin 2026-10-05 (ShiftTestHelpers).
 */
class ShiftChangeRequestTest extends TestCase
{
    use RefreshDatabase;
    use ShiftTestHelpers;

    private User $mentor;

    private User $pembimbing;

    private Intern $intern;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShiftWorld();
        $this->withoutVite();
        Notification::fake();

        $this->mentor = $this->makeUser(UserRole::Mentor);
        $this->pembimbing = $this->makeUser(UserRole::Pembimbing);
        $this->intern = $this->makeIntern(['mentor_id' => $this->mentor->id, 'pembimbing_id' => $this->pembimbing->id]);
        $this->intern->update(['uses_shift' => true]); // admin: memakai jadwal shift = Ya
    }

    private function requests(): ShiftChangeRequestService
    {
        return app(ShiftChangeRequestService::class);
    }

    /** @param  array<string, string>  $map  [Y-m-d => pagi|siang|malam|libur] */
    private function submit(array $map, ?string $reason = 'Ada urusan keluarga', ?Intern $intern = null, ?User $actor = null): array
    {
        $intern ??= $this->intern;
        $entries = [];
        foreach ($map as $date => $shift) {
            $entries[] = ['date' => $date, 'shift' => $shift];
        }

        return $this->requests()->submitEntries($actor ?? $intern->user, $intern, $entries, $reason);
    }

    private function assignment(string $date): ?InternShiftAssignment
    {
        return InternShiftAssignment::where('intern_id', $this->intern->id)->whereDate('date', $date)->first();
    }

    /** Isian awal oleh mentor (jalur koreksi biasa). */
    private function mentorSets(string $date, string $action = 'shift', int|string|null $shiftId = null): void
    {
        app(ShiftAssignmentService::class)->apply($this->mentor, $this->intern, [$date], $action, $shiftId ?? $this->pagi->id);
    }

    private function errorOf(callable $fn): array
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            return $e->errors();
        }
        $this->fail('Seharusnya ditolak (ValidationException).');
    }

    public function test_tanggal_kosong_pun_jadi_pengajuan_bukan_langsung_terisi(): void
    {
        $result = $this->submit(['2026-10-06' => 'pagi', '2026-10-07' => 'siang']);

        $this->assertSame(['2026-10-06', '2026-10-07'], $result['requested']);
        $this->assertSame(0, InternShiftAssignment::count());
        $this->assertSame(['Kosong → Pagi', 'Kosong → Siang'], ShiftChangeRequest::orderBy('date')->get()->map->changeLabel()->all());
    }

    public function test_mengganti_isian_jadi_pengajuan_dan_jadwal_belum_berubah(): void
    {
        $this->mentorSets('2026-10-07');

        $result = $this->submit(['2026-10-07' => 'siang']);
        $request = ShiftChangeRequest::sole();

        $this->assertSame(['2026-10-07'], $result['requested']);
        $this->assertSame(ShiftChangeRequest::STATUS_PENDING, $request->status);
        $this->assertSame('Pagi → Siang', $request->changeLabel());
        $this->assertSame($this->siang->id, $request->requested_shift_id);
        $this->assertSame('Siang', $request->requested_shift_code);
        $this->assertSame($this->pagi->id, $this->assignment('2026-10-07')->shift_id, 'Jadwal belum boleh berubah sebelum disetujui');

        Notification::assertSentTo($this->mentor, ShiftChangeRequested::class);
        Notification::assertNotSentTo($this->pembimbing, ShiftChangeRequested::class);
    }

    public function test_libur_dicatat_seperti_jadwal_harian_tanpa_shift(): void
    {
        $this->submit(['2026-10-08' => 'libur']);
        $request = ShiftChangeRequest::sole();

        $this->assertTrue($request->requested_off_day);
        $this->assertNull($request->requested_shift_id);
        $this->assertNull($request->requested_shift_code);
        $this->assertSame('Kosong → Libur', $request->changeLabel());

        $this->requests()->decide($this->mentor, $request, true);

        $row = $this->assignment('2026-10-08');
        $this->assertTrue($row->off_day);
        $this->assertNull($row->shift_id);
    }

    public function test_shift_tanpa_master_dicatat_jenisnya_dan_master_dibuat_saat_disetujui(): void
    {
        $this->assertFalse(Shift::where('company_id', $this->company->id)->where('code', 'Malam')->exists());

        $this->submit(['2026-10-09' => 'malam']);
        $request = ShiftChangeRequest::sole();

        $this->assertNull($request->requested_shift_id);
        $this->assertSame('Malam', $request->requested_shift_code);
        $this->assertSame('Kosong → Malam', $request->changeLabel());
        $this->assertFalse(Shift::where('code', 'Malam')->exists(), 'Intern tidak membuat master shift');

        $this->requests()->decide($this->mentor, $request, true);

        $malam = Shift::where('company_id', $this->company->id)->where('code', 'Malam')->sole();
        $this->assertSame('20:00', substr($malam->start_time, 0, 5));
        $this->assertSame('08:00', substr($malam->end_time, 0, 5));
        $this->assertSame($malam->id, $this->assignment('2026-10-09')->shift_id);
    }

    public function test_tanpa_alasan_gagal_dan_tidak_menyimpan_apa_pun(): void
    {
        $errors = $this->errorOf(fn () => $this->submit(['2026-10-06' => 'siang', '2026-10-07' => 'libur'], '   '));

        $this->assertArrayHasKey('reason', $errors);
        $this->assertSame(0, ShiftChangeRequest::count());
        Notification::assertNothingSent();
    }

    public function test_alasan_per_tanggal_dan_alasan_umum_sebagai_cadangan(): void
    {
        $this->requests()->submitEntries($this->intern->user, $this->intern, [
            ['date' => '2026-10-06', 'shift' => 'siang', 'reason' => 'Kontrol ke dokter'],
            ['date' => '2026-10-07', 'shift' => 'libur', 'reason' => '  '],          // kosong → alasan umum
            ['date' => '2026-10-08', 'shift' => 'malam'],                             // tanpa → alasan umum
        ], 'Urusan keluarga');

        $this->assertSame(
            ['2026-10-06' => 'Kontrol ke dokter', '2026-10-07' => 'Urusan keluarga', '2026-10-08' => 'Urusan keluarga'],
            ShiftChangeRequest::orderBy('date')->get()->mapWithKeys(fn ($r) => [$r->date->toDateString() => $r->reason])->all(),
        );

        // Alasan berbeda → tiap baris notifikasi membawa alasannya sendiri, tanpa baris "Alasan:" umum.
        Notification::assertSentTo($this->mentor, ShiftChangeRequested::class, function (ShiftChangeRequested $n) {
            $body = explode("\n", $n->toWebPush($this->mentor, $n)->toArray()['body']);

            return count($body) === 3
                && str_ends_with($body[0], 'Kosong → Siang — Kontrol ke dokter')
                && str_ends_with($body[1], 'Kosong → Libur — Urusan keluarga');
        });
    }

    public function test_tanpa_alasan_umum_semua_tanggal_wajib_punya_alasan_sendiri(): void
    {
        // Semua tanggal punya alasan sendiri → boleh tanpa alasan umum.
        $result = $this->requests()->submitEntries($this->intern->user, $this->intern, [
            ['date' => '2026-10-06', 'shift' => 'siang', 'reason' => 'Kuliah pagi'],
            ['date' => '2026-10-07', 'shift' => 'libur', 'reason' => 'Acara keluarga'],
        ], '');
        $this->assertSame(['2026-10-06', '2026-10-07'], $result['requested']);

        // Satu tanggal tanpa alasan & tanpa alasan umum → seluruh batch ditolak.
        $errors = $this->errorOf(fn () => $this->requests()->submitEntries($this->intern->user, $this->intern, [
            ['date' => '2026-10-08', 'shift' => 'siang', 'reason' => 'Kuliah pagi'],
            ['date' => '2026-10-09', 'shift' => 'libur'],
        ], ''));
        $this->assertArrayHasKey('reason', $errors);
        $this->assertSame(2, ShiftChangeRequest::count());
    }

    public function test_tanggal_dengan_pengajuan_menunggu_dan_pilihan_yang_sama_dilewati(): void
    {
        $this->mentorSets('2026-10-07');                    // Pagi
        $this->submit(['2026-10-08' => 'siang']);           // sudah ada pengajuan menunggu

        $result = $this->submit(['2026-10-07' => 'pagi', '2026-10-08' => 'libur', '2026-10-09' => 'siang']);

        $this->assertSame(['2026-10-09'], $result['requested']);
        $this->assertSame(['2026-10-07'], $result['unchanged']);
        $this->assertSame(['2026-10-08'], $result['pending']);
        $this->assertSame(['2026-10-07', '2026-10-08'], $result['skipped']);
        $this->assertSame(2, ShiftChangeRequest::count());
    }

    public function test_intern_tanpa_mentor_tidak_bisa_mengajukan(): void
    {
        $this->intern->update(['mentor_id' => null]);

        $errors = $this->errorOf(fn () => $this->submit(['2026-10-07' => 'siang'], 'Alasan', $this->intern->fresh()));

        $this->assertStringContainsString('belum punya mentor', collect($errors)->flatten()->first());
        $this->assertSame(0, ShiftChangeRequest::count());
    }

    public function test_intern_tanpa_shift_tidak_bisa_mengajukan(): void
    {
        $other = $this->makeIntern(['mentor_id' => $this->mentor->id]); // tidak terdaftar shift & tanpa jadwal

        $errors = $this->errorOf(fn () => $this->submit(['2026-10-07' => 'pagi'], 'Alasan', $other));

        $this->assertStringContainsString('belum terdaftar memakai jadwal shift', collect($errors)->flatten()->first());
        $this->assertSame(0, ShiftChangeRequest::count());
    }

    public function test_tidak_bisa_mengajukan_untuk_intern_lain(): void
    {
        $other = $this->makeIntern(['mentor_id' => $this->mentor->id]);

        $this->errorOf(fn () => $this->submit(['2026-10-07' => 'pagi'], 'Alasan', $this->intern, $other->user));
        $this->errorOf(fn () => $this->submit(['2026-10-07' => 'pagi'], 'Alasan', $this->intern, $this->mentor));

        $this->assertSame(0, ShiftChangeRequest::count());
    }

    public function test_hanya_mentor_terkait_yang_bisa_memutuskan(): void
    {
        $this->mentorSets('2026-10-07');
        $this->submit(['2026-10-07' => 'siang']);
        $request = ShiftChangeRequest::sole();

        foreach ([
            $this->makeUser(UserRole::Mentor),    // mentor lain
            $this->pembimbing,                   // pembimbing intern ini
            $this->makeUser(UserRole::Admin),
            $this->makeUser(UserRole::Pimpinan),
            $this->intern->user,
        ] as $actor) {
            $this->errorOf(fn () => $this->requests()->decide($actor, $request->fresh(), true));
        }

        $this->assertSame(ShiftChangeRequest::STATUS_PENDING, $request->fresh()->status);
        $this->assertSame($this->pagi->id, $this->assignment('2026-10-07')->shift_id);
    }

    public function test_mentor_menyetujui_jadwal_berubah_dan_intern_diberi_tahu(): void
    {
        $this->mentorSets('2026-10-07');
        $this->submit(['2026-10-07' => 'siang']);
        $request = ShiftChangeRequest::sole();

        $this->requests()->decide($this->mentor, $request, true, 'Oke, sudah konfirmasi');

        $request->refresh();
        $this->assertSame(ShiftChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertSame($this->mentor->id, $request->decided_by);
        $this->assertSame('Oke, sudah konfirmasi', $request->decision_note);
        $this->assertNotNull($request->decided_at);
        $this->assertSame($this->siang->id, $this->assignment('2026-10-07')->shift_id);
        $this->assertSame($this->mentor->id, $this->assignment('2026-10-07')->updated_by);

        Notification::assertSentTo($this->intern->user, ShiftChangeDecided::class);
        Notification::assertNotSentTo($this->pembimbing, ShiftChangeDecided::class);

        // Tidak bisa diputuskan dua kali.
        $this->errorOf(fn () => $this->requests()->decide($this->mentor, $request, false));
    }

    public function test_mentor_menolak_jadwal_tetap(): void
    {
        $this->mentorSets('2026-10-07');
        $this->submit(['2026-10-07' => 'siang']);
        $request = ShiftChangeRequest::sole();

        $this->requests()->decide($this->mentor, $request, false, 'Tidak ada pengganti');

        $this->assertSame(ShiftChangeRequest::STATUS_REJECTED, $request->fresh()->status);
        $this->assertSame($this->pagi->id, $this->assignment('2026-10-07')->shift_id);
        Notification::assertSentTo($this->intern->user, ShiftChangeDecided::class);
    }

    public function test_batas_30_hari_ke_depan(): void
    {
        // 2026-10-05 + 30 hari = 2026-11-04 (masih boleh), 2026-11-05 dilewati.
        $result = $this->submit(['2026-11-04' => 'pagi', '2026-11-05' => 'pagi']);

        $this->assertSame(['2026-11-04'], $result['requested']);
        $this->assertSame(['2026-11-05'], $result['locked']);
    }

    public function test_tanggal_lampau_dan_bertap_dilewati_untuk_intern_tetapi_mentor_bisa_mengubah(): void
    {
        AccessScanLog::create([
            'employee_id' => $this->intern->nip, 'intern_id' => $this->intern->id, 'nip' => $this->intern->nip,
            'matched' => true, 'scanned_at' => '2026-10-05 08:25:00', 'scan_date' => '2026-10-05', 'scan_time' => '08:25:00',
        ]);

        $result = $this->submit(['2026-10-04' => 'siang', '2026-10-05' => 'siang', '2026-10-06' => 'siang']);
        $this->assertSame(['2026-10-06'], $result['requested']);
        $this->assertSame(['2026-10-04', '2026-10-05'], $result['locked']);

        $saved = app(ShiftAssignmentService::class)->apply($this->mentor, $this->intern, ['2026-10-04', '2026-10-05'], 'shift', $this->siang->id);
        $this->assertSame(['2026-10-04', '2026-10-05'], $saved['saved']);
    }

    public function test_intern_membatalkan_pengajuan(): void
    {
        $this->mentorSets('2026-10-07');
        $this->submit(['2026-10-07' => 'siang']);
        $request = ShiftChangeRequest::sole();

        $this->errorOf(fn () => $this->requests()->cancel($this->makeIntern()->user, $request));

        $this->requests()->cancel($this->intern->user, $request);

        $this->assertSame(ShiftChangeRequest::STATUS_CANCELLED, $request->fresh()->status);
        $this->assertSame($this->pagi->id, $this->assignment('2026-10-07')->shift_id);

        // Yang sudah dibatalkan tidak bisa diputuskan / dibatalkan lagi.
        $this->errorOf(fn () => $this->requests()->decide($this->mentor, $request->fresh(), true));
        $this->errorOf(fn () => $this->requests()->cancel($this->intern->user, $request->fresh()));
    }

    public function test_alur_lewat_halaman_intern_dan_mentor(): void
    {
        $this->mentorSets('2026-10-07');

        Livewire::actingAs($this->intern->user)
            ->test(InternShifts::class)
            ->call('submitBulkShiftRequests', [
                ['date' => '2026-10-06', 'shift' => 'siang'],
                ['date' => '2026-10-07', 'shift' => 'libur'],
            ], 'Ada kuliah pagi')
            ->assertDispatched('shift-toast', type: 'success', message: '2 diajukan, 0 dilewati.')
            ->assertSee('Kosong → Siang')
            ->assertSee('Pagi → Libur');

        $this->assertNull($this->assignment('2026-10-06'));
        $request = ShiftChangeRequest::whereDate('date', '2026-10-07')->sole();

        Livewire::actingAs($this->mentor)
            ->test(PembimbingShifts::class)
            ->assertSee('Ada kuliah pagi')
            ->set("decisionNotes.{$request->id}", 'Silakan')
            ->call('decide', $request->id, true);

        $this->assertSame(ShiftChangeRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertTrue($this->assignment('2026-10-07')->off_day);

        // Pembimbing tidak melihat panel keputusan.
        Livewire::actingAs($this->pembimbing)
            ->test(PembimbingShifts::class)
            ->assertDontSee('Pengajuan perubahan shift');
    }
}
