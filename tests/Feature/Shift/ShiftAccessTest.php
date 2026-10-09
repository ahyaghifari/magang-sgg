<?php

namespace Tests\Feature\Shift;

use App\Enums\UserRole;
use App\Livewire\Pembimbing\Shifts as PembimbingShifts;
use App\Models\AttendanceRecord;
use App\Models\InternShiftAssignment;
use App\Services\Shift\ShiftAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Hak ubah jadwal shift: HANYA Mentor (dampingannya), tanggal mana saja. Intern, Pembimbing
 * (termasuk binaannya sendiri), Admin, Pimpinan, mentor lain: baca-saja. Plus updated_by dan hitung ulang.
 */
class ShiftAccessTest extends TestCase
{
    use RefreshDatabase;
    use ShiftTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShiftWorld();
        $this->withoutVite();
    }

    private function service(): ShiftAssignmentService
    {
        return app(ShiftAssignmentService::class);
    }

    public function test_intern_tidak_bisa_mengubah_jadwalnya_sendiri(): void
    {
        $intern = $this->makeIntern();

        // hari ini = 2026-10-05; tanggal lampau, hari ini, maupun ke depan semuanya ditolak.
        $result = $this->service()->apply($intern->user, $intern, ['2026-10-04', '2026-10-05', '2026-10-06'], 'off');

        $this->assertSame([], $result['saved']);
        $this->assertSame(['2026-10-04', '2026-10-05', '2026-10-06'], $result['locked']);
        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_admin_dan_pimpinan_hanya_melihat(): void
    {
        $intern = $this->makeIntern();

        foreach ([$this->makeUser(UserRole::Admin), $this->makeUser(UserRole::Pimpinan)] as $actor) {
            $result = $this->service()->apply($actor, $intern, ['2026-10-06'], 'off');
            $this->assertSame(['2026-10-06'], $result['locked'], $actor->role->value . ' seharusnya tidak bisa mengubah');
        }

        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_pembimbing_lain_dan_mentor_bukan_dampingan_tidak_bisa_mengubah(): void
    {
        $intern = $this->makeIntern([
            'pembimbing_id' => $this->makeUser(UserRole::Pembimbing)->id,
            'mentor_id' => $this->makeUser(UserRole::Mentor)->id,
        ]);

        foreach ([$this->makeUser(UserRole::Pembimbing), $this->makeUser(UserRole::Mentor)] as $actor) {
            $result = $this->service()->apply($actor, $intern, ['2026-10-06'], 'off');
            $this->assertSame(['2026-10-06'], $result['locked'], $actor->role->value . ' seharusnya tidak bisa mengubah');
        }

        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_pembimbing_binaannya_sendiri_hanya_melihat(): void
    {
        $pembimbing = $this->makeUser(UserRole::Pembimbing);
        $intern = $this->makeIntern(['pembimbing_id' => $pembimbing->id]);

        $result = $this->service()->apply($pembimbing, $intern, ['2026-10-01', '2026-10-06'], 'off');

        $this->assertSame(['2026-10-01', '2026-10-06'], $result['locked']);
        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_mentor_bisa_mengisi_tanggal_lampau_dan_ke_depan_dan_tercatat_updated_by(): void
    {
        $oldMentor = $this->makeUser(UserRole::Mentor);
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $oldMentor->id]);
        $this->service()->apply($oldMentor, $intern, ['2026-10-08'], 'off');
        $intern->update(['mentor_id' => $mentor->id]); // mentor berganti

        $result = $this->service()->apply($mentor, $intern->fresh(), ['2026-10-01', '2026-10-05', '2026-10-08'], 'shift', $this->siang->id);

        $this->assertSame(['2026-10-01', '2026-10-05', '2026-10-08'], $result['saved']);
        $row = InternShiftAssignment::where('intern_id', $intern->id)->whereDate('date', '2026-10-08')->first();
        $this->assertSame($oldMentor->id, $row->created_by);
        $this->assertSame($mentor->id, $row->updated_by);
        $this->assertSame($this->siang->id, $row->shift_id);
    }

    public function test_mentor_bisa_mengubah_dampingannya(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);

        $this->assertSame(['2026-10-02'], $this->service()->apply($mentor, $intern, ['2026-10-02'], 'off')['saved']);
    }

    public function test_mentor_bisa_memilih_semua_shift_perusahaan_peserta(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);
        $intern->update(['uses_shift' => true]); // admin: memakai jadwal shift = Ya

        $result = $this->service()->apply($mentor, $intern, ['2026-10-06'], 'shift', $this->siang->id);

        $this->assertSame(['2026-10-06'], $result['saved']);
    }

    public function test_shift_perusahaan_lain_ditolak(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);
        $foreign = \App\Models\Shift::factory()->create(); // perusahaan lain

        $this->expectException(ValidationException::class);
        $this->service()->apply($mentor, $intern, ['2026-10-06'], 'shift', $foreign->id);
    }

    public function test_peserta_tanpa_unit_mendapat_pesan_data_unit(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['unit_id' => null, 'mentor_id' => $mentor->id]);

        try {
            $this->service()->apply($mentor, $intern, ['2026-10-06'], 'shift', $this->pagi->id);
            $this->fail('Seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertSame('Data unit peserta ini belum lengkap, silakan hubungi admin.', collect($e->errors())->flatten()->first());
        }
    }

    public function test_koreksi_tanggal_lampau_menghitung_ulang_presensi(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);

        // Tercatat dengan jadwal tetap 08:00 → telat 50 menit.
        $this->syncTaps($intern, '2026-10-01', ['08:50:00', '17:00:00']);
        $this->assertSame(50, AttendanceRecord::where('nip', $intern->nip)->first()->late_minutes);

        // Ternyata hari itu shift Siang (12:00) → setelah koreksi tidak telat, pulang cepat 240 menit.
        $result = $this->service()->apply($mentor, $intern, ['2026-10-01'], 'shift', $this->siang->id);

        $this->assertSame(['2026-10-01'], $result['past_changed']);
        $r = AttendanceRecord::where('nip', $intern->nip)->first();
        $this->assertSame(1, AttendanceRecord::where('nip', $intern->nip)->count());
        $this->assertSame(0, $r->late_minutes);
        $this->assertSame(240, $r->early_leave_minutes);
        $this->assertSame('present', $r->status);
    }

    public function test_koreksi_menjadi_libur_tidak_menghapus_presensi_yang_ada(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);
        $this->syncTaps($intern, '2026-10-01', ['08:00:00', '16:00:00']);

        $this->service()->apply($mentor, $intern, ['2026-10-01'], 'off');

        $this->assertSame(1, AttendanceRecord::where('nip', $intern->nip)->count());
    }

    public function test_halaman_jadwal_per_peran(): void
    {
        $pembimbing = $this->makeUser(UserRole::Pembimbing);
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['pembimbing_id' => $pembimbing->id, 'mentor_id' => $mentor->id]);
        $intern->update(['uses_shift' => true]); // admin: memakai jadwal shift = Ya
        $this->service()->apply($mentor, $intern, ['2026-10-06'], 'shift', $this->pagi->id);
        $pimpinan = $this->makeUser(UserRole::Pimpinan);

        // Intern: baca-saja, tanpa panel aksi.
        $this->actingAs($intern->user)->get(route('shifts.index'))
            ->assertOk()->assertSee('Jadwal Shift')->assertSee('Pagi')->assertDontSee('Kosongkan');
        $this->actingAs($intern->user)->get(route('pembimbing.shifts'))->assertRedirect(route('home'));

        $this->actingAs($pembimbing)->get(route('shifts.index'))->assertRedirect(route('home'));
        $this->actingAs($pembimbing)->get(route('pembimbing.shifts', ['intern' => $intern->id]))->assertOk()->assertSee($intern->nama);

        // Pimpinan: tidak ada halaman terpisah — jadwal shift ada di dashboard-nya.
        $this->actingAs($pimpinan)->get(route('pembimbing.shifts'))->assertRedirect(route('pimpinan.dashboard'));
        $this->actingAs($pimpinan)->get(route('pimpinan.dashboard'))->assertOk()->assertSee('Jadwal Shift Peserta Magang');
    }

    public function test_livewire_mentor_memilih_beberapa_tanggal_lalu_menerapkan_shift(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);

        Livewire::actingAs($mentor)->test(PembimbingShifts::class, ['internId' => (string) $intern->id])
            ->call('toggleDate', '2026-10-04')
            ->call('toggleDate', '2026-10-06')
            ->assertSet('selected', ['2026-10-04', '2026-10-06'])
            ->call('apply', 'shift', $this->pagi->id)
            ->assertSet('selected', []);

        $this->assertSame(2, InternShiftAssignment::where('intern_id', $intern->id)->where('shift_id', $this->pagi->id)->count());
    }

    public function test_livewire_admin_dan_pimpinan_tidak_bisa_mengubah(): void
    {
        $intern = $this->makeIntern();

        foreach ([
            [$this->makeUser(UserRole::Admin), []],
            [$this->makeUser(UserRole::Pimpinan), ['embedded' => true]],
        ] as [$actor, $params]) {
            Livewire::actingAs($actor)->test(PembimbingShifts::class, $params + ['internId' => (string) $intern->id])
                ->assertSee('Hanya lihat')
                ->call('toggleDate', '2026-10-06')
                ->assertSet('selected', [])
                ->set('selected', ['2026-10-06'])
                ->call('apply', 'off');
        }

        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_pembimbing_hanya_melihat_binaannya_di_halaman_jadwal(): void
    {
        $pembimbing = $this->makeUser(UserRole::Pembimbing);
        $binaan = $this->makeIntern(['pembimbing_id' => $pembimbing->id]);
        $bukan = $this->makeIntern();

        Livewire::actingAs($pembimbing)->test(PembimbingShifts::class)
            ->assertSee($binaan->nama)
            ->assertDontSee($bukan->nama);
    }

    public function test_pagi_siang_dan_malam_selalu_bisa_dipilih_mentor_walau_master_shift_belum_ada(): void
    {
        $this->pagi->delete(); // perusahaan hanya punya master shift Siang
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);

        $this->assertSame(['Pagi', 'Siang', 'Malam'], $this->service()->pickableShifts($intern)->pluck('code')->all());

        Livewire::actingAs($mentor)->test(PembimbingShifts::class, ['internId' => (string) $intern->id])
            ->assertSee('Pagi')
            ->call('toggleDate', '2026-10-06')
            ->call('apply', 'shift', 'Pagi');

        // Master shift Pagi dibuat otomatis dengan jam bawaan, lalu dipakai di jadwal.
        $pagi = \App\Models\Shift::where('company_id', $this->company->id)->where('code', 'Pagi')->sole();
        $this->assertSame('08:00', substr($pagi->start_time, 0, 5));
        $this->assertSame('14:00', substr($pagi->end_time, 0, 5));
        $this->assertSame($pagi->id, InternShiftAssignment::where('intern_id', $intern->id)->sole()->shift_id);

        // Dipakai lagi → tidak membuat master shift ganda.
        $this->service()->apply($mentor, $intern, ['2026-10-07'], 'shift', 'Pagi');
        $this->assertSame(1, \App\Models\Shift::where('company_id', $this->company->id)->where('code', 'Pagi')->count());
    }

    public function test_yang_tidak_berhak_tidak_bisa_membuat_master_shift_lewat_jadwal(): void
    {
        $this->pagi->delete();
        $intern = $this->makeIntern();

        $intern->update(['pembimbing_id' => ($pembimbing = $this->makeUser(UserRole::Pembimbing))->id]);

        foreach ([$intern->user, $pembimbing, $this->makeUser(UserRole::Admin), $this->makeUser(UserRole::Pimpinan)] as $actor) {
            try {
                $this->service()->apply($actor, $intern, ['2026-10-06'], 'shift', 'Pagi');
                $this->fail('Seharusnya ditolak.');
            } catch (ValidationException) {
            }
        }

        $this->assertFalse(\App\Models\Shift::where('code', 'Pagi')->exists());
        $this->assertSame(0, InternShiftAssignment::count());
    }
}
