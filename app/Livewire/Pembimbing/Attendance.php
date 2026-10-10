<?php

namespace App\Livewire\Pembimbing;

use App\Models\AttendanceRecord;
use App\Models\Intern;
use App\Models\LeaveRequest;
use App\Services\Shift\ScheduleResolver;
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

        return $user && $user->isPortalMentor();
    }

    /** Intern yang boleh dilihat/dikelola user yang sedang login (lihat User::visibleInterns()). */
    protected function visibleInterns()
    {
        return auth()->user()->visibleInterns();
    }

    public function render()
    {
        $visibleNips = $this->visibleInterns()->whereNotNull('nip')->pluck('nip');

        $selectedNip = $this->internId !== ''
            ? Intern::whereKey($this->internId)->value('nip')
            : null;

        $base = AttendanceRecord::query()
            ->whereIn('nip', $visibleNips)
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

        // Izin/sakit yang disetujui, ditampilkan terpisah dari rekap presensi mesin sidik
        // jari — supaya hari intern tidak masuk karena izin/sakit tidak terlihat begitu
        // saja "hilang" dari daftar, dibedakan jelas dari yang benar-benar alfa.
        // Izin HANYA tampil di hari berlakunya: tanpa filter tanggal = yang berlaku hari ini
        // saja (bukan semua izin yang pernah ada); dengan filter = yang beririsan rentangnya.
        $today = Carbon::now('Asia/Makassar')->toDateString();
        $leavesFrom = $this->dateFrom !== '' ? $this->dateFrom : ($this->dateTo !== '' ? null : $today);
        $leavesTo = $this->dateTo !== '' ? $this->dateTo : ($this->dateFrom !== '' ? null : $today);

        $leaves = LeaveRequest::query()
            ->whereIn('intern_id', $this->visibleInterns()->pluck('id'))
            ->where('status', 'approved')
            ->when($this->internId !== '', fn ($q) => $q->where('intern_id', $this->internId))
            ->when($leavesFrom, fn ($q) => $q->whereDate('end_date', '>=', $leavesFrom))
            ->when($leavesTo, fn ($q) => $q->whereDate('start_date', '<=', $leavesTo))
            ->with('intern.unit')
            ->orderByDesc('start_date')
            ->get();

        return view('livewire.pembimbing.attendance', [
            'records' => $records,
            'shiftLabels' => ScheduleResolver::labelsForRecords($records),
            'summary' => $summary,
            'leaves' => $leaves,
            'interns' => $this->visibleInterns()->orderBy('nama')->get(['id', 'nama', 'nip']),
            'now' => Carbon::now('Asia/Makassar'),
        ]);
    }
}
