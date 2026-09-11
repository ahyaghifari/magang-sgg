<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\CompanyFixedSchedule;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Home extends Component
{
    /** Semua jam mesin absensi memakai waktu WITA (samakan dengan Attendance\Index). */
    private const TZ = 'Asia/Makassar';

    public bool $journalSaved = false;

    public function mount()
    {
        // Pembimbing tidak punya beranda peserta — arahkan ke feed kegiatan intern.
        // Super admin dikecualikan: di portal dia diperlakukan seperti intern biasa.
        if (auth()->user()->isPortalMentor()) {
            return $this->redirect(route('pembimbing.activities'), navigate: true);
        }
    }

    #[On('journal-saved')]
    public function onJournalSaved(): void
    {
        // Cukup menandai; render() otomatis menghitung ulang statistik & jurnal terbaru.
        $this->journalSaved = true;
    }

    public function render()
    {
        $user = auth()->user();
        $intern = $user->loadMissing('intern.unit')->intern;

        $journalsQuery = $intern?->journals();

        $now = Carbon::now(self::TZ);
        $schedule = null;
        $todayAttendance = null;

        if ($intern && filled($intern->nip)) {
            $companyId = $intern->unit?->company_id;

            $schedule = $companyId
                ? CompanyFixedSchedule::where('company_id', $companyId)
                    ->where('day_of_week', $now->dayOfWeek) // 0=Minggu … 6=Sabtu
                    ->first()
                : null;

            $todayAttendance = AttendanceRecord::where('nip', $intern->nip)
                ->whereDate('date', $now->toDateString())
                ->first();
        }

        return view('livewire.home', [
            'user' => $user,
            'intern' => $intern,
            'now' => $now,
            'schedule' => $schedule,
            'todayAttendance' => $todayAttendance,
            'recentJournals' => $journalsQuery
                ? (clone $journalsQuery)->latest('date')->take(5)->get()
                : collect(),
            'totalJournals' => $journalsQuery ? (clone $journalsQuery)->count() : 0,
            'journalsThisMonth' => $journalsQuery
                ? (clone $journalsQuery)->whereBetween('date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])->count()
                : 0,
            'lastJournalDate' => $journalsQuery
                ? (clone $journalsQuery)->max('date')
                : null,
        ]);
    }
}
