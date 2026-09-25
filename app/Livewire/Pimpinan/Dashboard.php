<?php

namespace App\Livewire\Pimpinan;

use App\Livewire\Concerns\HasInternDetailModals;
use App\Models\AttendanceRecord;
use App\Models\Intern;
use App\Models\Journal;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Dashboard ringkasan READ-ONLY untuk peran pimpinan — tidak ada aksi kelola
 * (tidak menilai jurnal, tidak konfirmasi izin, tidak assign tugas). Menampilkan
 * SELURUH data (bukan cuma cuplikan): daftar semua intern (kartu + jurnal/tugas lengkap
 * seperti halaman Intern mentor), semua kegiatan/jurnal, semua presensi
 * (masuk & telat, per tanggal), dan semua pengajuan izin — masing-masing bisa
 * difilter & dipaginasi sendiri-sendiri.
 */
#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    use HasInternDetailModals;
    use WithPagination;

    private const TZ = 'Asia/Makassar';

    // ----- Filter: Seluruh Intern -----
    public string $internSearch = '';

    // ----- Filter: Kegiatan & Jurnal -----
    public string $kegiatanInternId = '';

    #[Url]
    public string $kegiatanDateFrom = '';

    #[Url]
    public string $kegiatanDateTo = '';

    // ----- Filter: Presensi -----
    public string $presensiInternId = '';

    #[Url]
    public string $presensiDateFrom = '';

    #[Url]
    public string $presensiDateTo = '';

    // ----- Filter: Izin -----
    public string $izinInternId = '';

    public string $izinStatus = '';

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }

        // Default presensi: hari ini saja, supaya tetap ringkas saat pertama dibuka.
        // Bisa diganti lewat filter tanggal untuk lihat hari lain.
        $today = Carbon::now(self::TZ)->toDateString();
        $this->presensiDateFrom = $today;
        $this->presensiDateTo = $today;
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && $user->isPimpinan();
    }

    protected function canViewIntern(int $internId): bool
    {
        return Intern::whereKey($internId)->exists();
    }

    public function updated(string $property): void
    {
        if ($property === 'internSearch') {
            $this->resetPage('internPage');
        }

        if (in_array($property, ['kegiatanInternId', 'kegiatanDateFrom', 'kegiatanDateTo'], true)) {
            $this->resetPage('kegiatanPage');
        }

        if (in_array($property, ['presensiInternId', 'presensiDateFrom', 'presensiDateTo'], true)) {
            $this->resetPage('presensiPage');
        }

        if (in_array($property, ['izinInternId', 'izinStatus'], true)) {
            $this->resetPage('izinPage');
        }
    }

    public function resetKegiatanFilter(): void
    {
        $this->reset(['kegiatanInternId', 'kegiatanDateFrom', 'kegiatanDateTo']);
        $this->resetPage('kegiatanPage');
    }

    public function resetPresensiFilter(): void
    {
        $today = Carbon::now(self::TZ)->toDateString();
        $this->presensiInternId = '';
        $this->presensiDateFrom = $today;
        $this->presensiDateTo = $today;
        $this->resetPage('presensiPage');
    }

    public function resetIzinFilter(): void
    {
        $this->reset(['izinInternId', 'izinStatus']);
        $this->resetPage('izinPage');
    }

    public function render()
    {
        $now = Carbon::now(self::TZ);

        // ----- Kegiatan & Jurnal (semua, difilter) -----
        $kegiatan = Journal::query()
            ->with(['intern.unit'])
            ->when($this->kegiatanInternId !== '', fn ($q) => $q->where('intern_id', $this->kegiatanInternId))
            ->when($this->kegiatanDateFrom !== '', fn ($q) => $q->whereDate('date', '>=', $this->kegiatanDateFrom))
            ->when($this->kegiatanDateTo !== '', fn ($q) => $q->whereDate('date', '<=', $this->kegiatanDateTo))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(8, pageName: 'kegiatanPage');

        // ----- Presensi (semua, difilter per tanggal/peserta) -----
        $selectedNip = $this->presensiInternId !== ''
            ? Intern::whereKey($this->presensiInternId)->value('nip')
            : null;

        $presensi = AttendanceRecord::query()
            ->with('intern.unit')
            ->when($this->presensiInternId !== '', function ($q) use ($selectedNip) {
                $selectedNip ? $q->where('nip', $selectedNip) : $q->whereRaw('1 = 0');
            })
            ->when($this->presensiDateFrom !== '', fn ($q) => $q->whereDate('date', '>=', $this->presensiDateFrom))
            ->when($this->presensiDateTo !== '', fn ($q) => $q->whereDate('date', '<=', $this->presensiDateTo))
            ->orderByDesc('date')
            ->orderBy('nip')
            ->paginate(10, pageName: 'presensiPage');

        $todayAttendance = AttendanceRecord::whereDate('date', $now->toDateString())->get();

        // ----- Izin (semua, difilter) -----
        $izin = LeaveRequest::query()
            ->with(['intern.unit'])
            ->when($this->izinInternId !== '', fn ($q) => $q->where('intern_id', $this->izinInternId))
            ->when($this->izinStatus !== '', fn ($q) => $q->where('status', $this->izinStatus))
            ->orderByRaw("field(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(8, pageName: 'izinPage');

        $allInterns = $this->internCardsQuery($this->internSearch)->paginate(9, pageName: 'internPage');

        return view('livewire.pimpinan.dashboard', [
            'allInterns' => $allInterns,
            ...$this->internDetailModalData(),
            'interns' => Intern::orderBy('nama')->get(['id', 'nama']),
            'totalInterns' => Intern::count(),
            'totalJournals' => Journal::count(),
            'journalsThisMonth' => Journal::whereBetween('date', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])->count(),
            'kegiatan' => $kegiatan,
            'presensi' => $presensi,
            'hadirHariIni' => $todayAttendance->whereNotNull('check_in_time')->count(),
            'telatHariIni' => $todayAttendance->where('late_minutes', '>', 0)->count(),
            'izin' => $izin,
            'izinPendingCount' => LeaveRequest::where('status', 'pending')->count(),
            'now' => $now,
        ]);
    }
}
