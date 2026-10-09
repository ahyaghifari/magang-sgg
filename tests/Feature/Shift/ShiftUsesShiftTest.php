<?php

namespace Tests\Feature\Shift;

use App\Enums\UserRole;
use App\Models\ShiftChangeRequest;
use App\Services\Shift\ShiftAssignmentService;
use App\Services\Shift\ShiftChangeRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Admin memilih per intern "Memakai jadwal shift: Ya / Tidak" (kolom interns.uses_shift) —
 * pengganti pemilihan intern di form Master Shift. Pilihan ini yang menentukan menu Jadwal Shift
 * dan hak mengajukan perubahan.
 */
class ShiftUsesShiftTest extends TestCase
{
    use RefreshDatabase;
    use ShiftTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShiftWorld();
        $this->withoutVite();
        Notification::fake();
    }

    public function test_menu_jadwal_shift_mengikuti_pilihan_admin(): void
    {
        $intern = $this->makeIntern();

        $this->actingAs($intern->user)->get(route('home'))->assertDontSee(route('shifts.index'));

        $intern->update(['uses_shift' => true]);

        $this->actingAs($intern->user->fresh())->get(route('home'))->assertSee(route('shifts.index'));
    }

    public function test_pilihan_tidak_menolak_pengajuan_walau_sudah_punya_jadwal(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $intern = $this->makeIntern(['mentor_id' => $mentor->id]);
        // Mentor tetap bisa mengisi jadwal; tetapi admin memilih "Tidak" → intern tidak bisa mengajukan.
        app(ShiftAssignmentService::class)->apply($mentor, $intern, ['2026-10-07'], 'shift', $this->pagi->id);

        try {
            app(ShiftChangeRequestService::class)->submitEntries($intern->user, $intern, [['date' => '2026-10-07', 'shift' => 'siang']], 'Alasan');
            $this->fail('Seharusnya ditolak.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('belum terdaftar memakai jadwal shift', collect($e->errors())->flatten()->first());
        }

        $this->assertSame(0, ShiftChangeRequest::count());
    }

    public function test_migrasi_mengisi_ya_hanya_untuk_intern_yang_dulu_dipilih_admin_di_master_shift(): void
    {
        $mentor = $this->makeUser(UserRole::Mentor);
        $terdaftar = $this->makeIntern();
        $punyaJadwal = $this->makeIntern(['mentor_id' => $mentor->id]);
        $lainnya = $this->makeIntern();

        DB::table('intern_shift')->insert(['intern_id' => $terdaftar->id, 'shift_id' => $this->pagi->id]);
        app(ShiftAssignmentService::class)->apply($mentor, $punyaJadwal, ['2026-10-07'], 'off');

        $migration = require database_path('migrations/2026_10_09_120000_add_uses_shift_to_interns_table.php');
        $migration->down();
        $migration->up();

        $this->assertTrue($terdaftar->fresh()->usesShifts());
        $this->assertFalse($punyaJadwal->fresh()->usesShifts(), 'Sekadar punya isian jadwal tidak dihitung');
        $this->assertFalse($lainnya->fresh()->usesShifts());
    }
}
