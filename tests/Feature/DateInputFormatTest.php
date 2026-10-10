<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Journals\Index as JournalIndex;
use App\Livewire\Pembimbing\Attendance as PembimbingAttendance;
use App\Models\User;
use Database\Factories\InternFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Input tanggal portal tampil sebagai DD/MM/YYYY (komponen x-date-input), sementara nilai yang
 * dikirim ke Livewire tetap Y-m-d — filter tanggal yang sudah ada tidak berubah perilakunya.
 */
class DateInputFormatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_filter_tanggal_intern_tampil_dd_mm_yyyy_dan_menerima_nilai_y_m_d(): void
    {
        $intern = InternFactory::new()->create();

        Livewire::actingAs($intern->user)
            ->test(JournalIndex::class)
            ->assertSeeHtml('placeholder="DD/MM/YYYY"')
            ->assertDontSeeHtml('type="date" wire:model')
            ->set('dateFrom', '2026-10-01')
            ->assertSet('dateFrom', '2026-10-01')
            ->assertSeeHtml('$entangle(\'dateFrom\', true)');
    }

    public function test_filter_tanggal_pembimbing_tampil_dd_mm_yyyy(): void
    {
        $pembimbing = User::factory()->role(UserRole::Pembimbing)->create();

        Livewire::actingAs($pembimbing)
            ->test(PembimbingAttendance::class)
            ->assertSeeHtml('placeholder="DD/MM/YYYY"')
            ->set('dateFrom', '2026-10-01')
            ->set('dateTo', '2026-10-05')
            ->assertHasNoErrors();
    }
}
