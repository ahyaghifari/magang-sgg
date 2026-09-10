<?php

namespace App\Livewire\Attendance;

use App\Models\AccessScanLog;
use App\Models\AttendanceRecord;
use App\Models\CompanyFixedSchedule;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tampilan presensi untuk intern — READ-ONLY. Data berasal dari mesin sidik jari
 * (pipeline `attendance:sync`), dicocokkan lewat NIP. Intern tidak menekan tombol
 * absen di sini; halaman ini hanya menampilkan hasil tap-nya.
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    use WithPagination;

    /** Semua jam mesin absensi memakai waktu WITA. */
    private const TZ = 'Asia/Makassar';

    /** Bulan yang ditampilkan pada Riwayat & Ringkasan (format Y-m). Kosong = bulan berjalan. */
    #[Url]
    public string $month = '';

    public function updatingMonth(): void
    {
        $this->resetPage();
    }

    public function resetMonth(): void
    {
        $this->month = '';
        $this->resetPage();
    }

    public function render()
    {
        $intern = auth()->user()->loadMissing('intern.unit')->intern;
        $now = Carbon::now(self::TZ);
        $todayStr = $now->toDateString();

        // Bulan terpilih (default: bulan berjalan). Divalidasi longgar: kalau formatnya
        // aneh, jatuh ke bulan berjalan.
        try {
            $selectedMonth = $this->month !== ''
                ? Carbon::createFromFormat('Y-m', $this->month, self::TZ)->startOfMonth()
                : $now->copy()->startOfMonth();
        } catch (\Throwable) {
            $selectedMonth = $now->copy()->startOfMonth();
        }

        $monthStart = $selectedMonth->copy()->startOfMonth()->toDateString();
        $monthEnd = $selectedMonth->copy()->endOfMonth()->toDateString();

        $schedule = null;
        $today = null;
        $records = null;
        $todayTaps = collect();
        $monthStats = null;

        if ($intern && filled($intern->nip)) {
            $companyId = $intern->unit?->company_id;

            $schedule = $companyId
                ? CompanyFixedSchedule::where('company_id', $companyId)
                    ->where('day_of_week', $now->dayOfWeek) // 0=Minggu … 6=Sabtu
                    ->first()
                : null;

            $today = AttendanceRecord::where('nip', $intern->nip)
                ->whereDate('date', $todayStr)
                ->first();

            $records = AttendanceRecord::where('nip', $intern->nip)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->orderByDesc('date')
                ->paginate(14);

            $todayTaps = AccessScanLog::where('nip', $intern->nip)
                ->whereDate('scan_date', $todayStr)
                ->orderBy('scanned_at')
                ->get();

            $monthRows = AttendanceRecord::where('nip', $intern->nip)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->get();

            $monthStats = [
                'hadir' => $monthRows->count(),
                'telat' => $monthRows->where('late_minutes', '>', 0)->count(),
                'menit_telat' => (int) $monthRows->sum('late_minutes'),
            ];
        }

        return view('livewire.attendance.index', [
            'intern' => $intern,
            'now' => $now,
            'selectedMonth' => $selectedMonth,
            'isCurrentMonth' => $selectedMonth->isSameMonth($now),
            'schedule' => $schedule,
            'today' => $today,
            'todayTaps' => $todayTaps,
            'records' => $records,
            'monthStats' => $monthStats,
        ]);
    }
}
