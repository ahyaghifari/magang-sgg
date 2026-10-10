<?php

namespace Tests\Feature\Shift;

use App\Enums\UserRole;
use App\Livewire\Pembimbing\Shifts as PembimbingShifts;
use App\Livewire\Shifts\Index as InternShifts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pilih cepat di kalender jadwal shift: ketuk nama hari (semua hari itu di bulan ini) atau
 * tombol baris (satu minggu). "Hari ini" = Senin 2026-10-05; 1 Oktober 2026 = Kamis.
 */
class ShiftQuickPickTest extends TestCase
{
    use RefreshDatabase;
    use ShiftTestHelpers;

    private const MONDAYS = ['2026-10-05', '2026-10-12', '2026-10-19', '2026-10-26'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShiftWorld();
        $this->withoutVite();
    }

    public function test_mentor_memilih_semua_senin_lalu_ketuk_lagi_untuk_melepas(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);

        Livewire::actingAs($mentor)
            ->test(PembimbingShifts::class, ['internId' => (string) $intern->id])
            ->assertSee('Pilih semua hari Senin bulan ini')
            ->call('toggleDate', '2026-10-12')               // sebagian sudah terpilih
            ->call('toggleDates', self::MONDAYS)
            ->assertSet('selected', ['2026-10-12', '2026-10-05', '2026-10-19', '2026-10-26'])
            ->call('toggleDates', self::MONDAYS)               // semua terpilih → dilepas
            ->assertSet('selected', [])
            ->call('toggleDates', ['2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04']) // minggu pertama
            ->call('apply', 'off');

        $this->assertSame(4, $intern->shiftAssignments()->where('off_day', true)->count());
    }

    public function test_yang_hanya_melihat_tidak_punya_pilih_cepat(): void
    {
        $admin = $this->makeUser(UserRole::Admin);
        $intern = $this->makeIntern();

        Livewire::actingAs($admin)
            ->test(PembimbingShifts::class, ['internId' => (string) $intern->id])
            ->assertDontSee('Pilih semua hari Senin bulan ini')
            ->call('toggleDates', self::MONDAYS)
            ->assertSet('selected', []);
    }

    public function test_intern_pilih_cepat_melewati_tanggal_terkunci(): void
    {
        $intern = $this->makeIntern(['mentor_id' => $this->makeUser(UserRole::Mentor)->id]);
        $intern->update(['uses_shift' => true]); // admin: memakai jadwal shift = Ya

        Livewire::actingAs($intern->user)
            ->test(InternShifts::class)
            ->assertSee('Pilih semua hari Senin bulan ini')
            // 28 Sep sudah lewat, 9 Nov di luar batas 30 hari → dibuang.
            ->call('toggleDates', ['2026-09-28', ...self::MONDAYS, '2026-11-09'])
            ->assertSet('selected', self::MONDAYS)
            ->call('submitBulkShiftRequests', array_map(fn ($d) => ['date' => $d, 'shift' => 'pagi'], self::MONDAYS), 'Kuliah pagi');

        // Intern tidak mengubah jadwal langsung — semuanya jadi pengajuan.
        $this->assertSame(0, $intern->shiftAssignments()->count());
        $this->assertSame(4, $intern->shiftChangeRequests()->count());
    }
}
