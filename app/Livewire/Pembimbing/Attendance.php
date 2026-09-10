<?php

namespace App\Livewire\Pembimbing;

use App\Models\AttendanceRecord;
use App\Models\Intern;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Riwayat presensi SEMUA peserta magang (hasil `attendance:sync`) — untuk peran
 * pembimbing (dan admin). READ-ONLY, dicocokkan ke data intern lewat NIP.
 */
#[Layout('components.layouts.app')]
class Attendance extends Component
{
    use WithPagination;

    public string $internId = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    public function updated($property): void
    {
        if (in_array($property, ['internId', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['internId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && ($user->isPembimbing() || $user->isAdmin());
    }

    public function render()
    {
        $selectedNip = $this->internId !== ''
            ? Intern::whereKey($this->internId)->value('nip')
            : null;

        $base = AttendanceRecord::query()
            ->when($this->internId !== '', function ($q) use ($selectedNip) {
                // NIP kosong → tak mungkin ada rekap; paksa hasil kosong.
                $selectedNip ? $q->where('nip', $selectedNip) : $q->whereRaw('1 = 0');
            })
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date', '<=', $this->dateTo));

        $records = (clone $base)
            ->with('intern.unit')
            ->orderByDesc('date')
            ->orderBy('nip')
            ->paginate(20);

        $summary = [
            'total' => (clone $base)->count(),
            'telat' => (clone $base)->where('late_minutes', '>', 0)->count(),
            'menit_telat' => (int) (clone $base)->sum('late_minutes'),
        ];

        return view('livewire.pembimbing.attendance', [
            'records' => $records,
            'summary' => $summary,
            'interns' => Intern::orderBy('nama')->get(['id', 'nama', 'nip']),
            'now' => Carbon::now('Asia/Makassar'),
        ]);
    }
}
