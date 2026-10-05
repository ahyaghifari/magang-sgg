<?php

namespace Tests\Feature\Shift;

use App\Enums\UserRole;
use App\Livewire\Leaves\Create as LeaveCreate;
use App\Models\InternShiftAssignment;
use App\Models\LeaveRequest;
use App\Notifications\LeaveRequestSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sistem izin TIDAK berubah karena jadwal shift: alur, validasi jam, notifikasi tetap sama;
 * izin tidak membuat/mengubah jadwal shift, dan jadwal shift tidak memengaruhi izin.
 */
class LeaveUnchangedTest extends TestCase
{
    use RefreshDatabase;
    use ShiftTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShiftWorld();
        Notification::fake();
    }

    public function test_pengajuan_izin_tetap_sama_untuk_intern_dengan_jadwal_shift(): void
    {
        $pembimbing = $this->makeUser(UserRole::Pembimbing);
        $intern = $this->makeIntern(['pembimbing_id' => $pembimbing->id]);
        $intern->shifts()->attach($this->siang->id);
        // Hari izin sudah diisi shift Siang & hari lain Libur.
        app(\App\Services\Shift\ShiftAssignmentService::class)->apply($pembimbing, $intern, ['2026-10-06'], 'shift', $this->siang->id);
        app(\App\Services\Shift\ShiftAssignmentService::class)->apply($pembimbing, $intern, ['2026-10-07'], 'off');
        $scheduleBefore = InternShiftAssignment::orderBy('id')->get(['intern_id', 'date', 'shift_id', 'off_day', 'updated_by'])->toArray();

        // Jam izin di luar jam shift (shift Siang 12:00) tetap diterima seperti sebelumnya.
        Livewire::actingAs($intern->user)->test(LeaveCreate::class)
            ->call('open')
            ->set('type', 'izin')
            ->set('startDate', '2026-10-06')
            ->set('endDate', '2026-10-07')
            ->set('startTime', '08:00')
            ->set('endTime', '10:00')
            ->set('reason', 'Keperluan keluarga')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('leave-saved');

        $leave = LeaveRequest::sole();
        $this->assertSame('pending', $leave->status);
        $this->assertSame('2026-10-06', $leave->start_date->toDateString());
        $this->assertSame('2026-10-07', $leave->end_date->toDateString());
        Notification::assertSentTo($pembimbing, LeaveRequestSubmitted::class);

        // Jadwal shift tidak tersentuh sama sekali oleh izin.
        $this->assertSame($scheduleBefore, InternShiftAssignment::orderBy('id')->get(['intern_id', 'date', 'shift_id', 'off_day', 'updated_by'])->toArray());
    }

    public function test_validasi_izin_tetap_sama(): void
    {
        $intern = $this->makeIntern();
        $intern->shifts()->attach($this->pagi->id);

        Livewire::actingAs($intern->user)->test(LeaveCreate::class)
            ->call('open')
            ->set('startDate', '2026-10-07')
            ->set('endDate', '2026-10-06')
            ->set('startTime', '10:00')
            ->set('endTime', '09:00')
            ->set('reason', 'abc')
            ->call('save')
            ->assertHasErrors(['endDate' => 'after_or_equal', 'endTime' => 'after', 'reason' => 'min']);

        Livewire::actingAs($intern->user)->test(LeaveCreate::class)
            ->call('open')
            ->set('startTime', '09:00')
            ->set('reason', 'Sakit kepala')
            ->call('save')
            ->assertHasErrors(['endTime' => 'required_with']);

        $this->assertSame(0, LeaveRequest::count());
        $this->assertSame(0, InternShiftAssignment::count());
    }

    public function test_izin_untuk_intern_tanpa_shift_tidak_membuat_jadwal(): void
    {
        $intern = $this->makeIntern();

        Livewire::actingAs($intern->user)->test(LeaveCreate::class)
            ->call('open')
            ->set('type', 'sakit')
            ->set('reason', 'Demam tinggi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, LeaveRequest::count());
        $this->assertSame(0, InternShiftAssignment::count());
    }
}
