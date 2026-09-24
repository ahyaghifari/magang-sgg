<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\CompanyFixedSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Home extends Component
{
    /** Semua jam mesin absensi memakai waktu WITA (samakan dengan Attendance\Index). */
    private const TZ = 'Asia/Makassar';

    public bool $journalSaved = false;

    // ===== Modal "Ubah Profil" — ganti foto profil & warna kartu beranda sendiri =====
    public bool $showProfileModal = false;

    /**
     * Hasil crop foto profil (data URL base64 dari canvas di sisi klien — lihat
     * cropperData() di home.blade.php). Foto ORIGINAL tidak pernah diupload ke server,
     * cuma versi yang sudah di-crop bulat 400x400 ini yang dikirim lewat $wire.set().
     */
    public string $avatarDataUrl = '';

    public string $dashboardColor = '';

    public function mount()
    {
        // Pembimbing/mentor tidak punya beranda peserta — arahkan ke feed kegiatan intern.
        // Super admin dikecualikan: di portal dia diperlakukan seperti intern biasa.
        if (auth()->user()->isPortalMentor()) {
            return $this->redirect(route('pembimbing.activities'), navigate: true);
        }

        // Pimpinan cuma punya dashboard ringkasan read-only, bukan beranda peserta.
        if (auth()->user()->isPimpinan()) {
            return $this->redirect(route('pimpinan.dashboard'), navigate: true);
        }
    }

    #[On('journal-saved')]
    public function onJournalSaved(): void
    {
        // Cukup menandai; render() otomatis menghitung ulang statistik & jurnal terbaru.
        $this->journalSaved = true;
    }

    public function openProfileModal(): void
    {
        $intern = auth()->user()->intern;

        if (! $intern) {
            return;
        }

        $this->dashboardColor = $intern->dashboard_color ?? '';
        $this->avatarDataUrl = '';
        $this->resetValidation();
        $this->showProfileModal = true;
    }

    public function closeProfileModal(): void
    {
        $this->showProfileModal = false;
        $this->avatarDataUrl = '';
        $this->resetValidation();
    }

    public function resetDashboardColor(): void
    {
        $this->dashboardColor = '';
    }

    public function saveProfile(): void
    {
        $intern = auth()->user()->intern;

        if (! $intern) {
            return;
        }

        $this->validate([
            'dashboardColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [], [
            'dashboardColor' => 'warna',
        ]);

        if ($this->avatarDataUrl !== '') {
            if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/', $this->avatarDataUrl, $matches)) {
                $this->addError('avatarDataUrl', 'Format foto tidak valid, coba pilih ulang.');

                return;
            }

            $binary = base64_decode($matches[2]);

            // Hasil crop selalu 400x400 dari canvas — batas ini cuma jaring pengaman kalau
            // ada payload yang dipalsukan/rusak, bukan batas normal (hasil asli jauh lebih kecil).
            if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
                $this->addError('avatarDataUrl', 'Foto gagal diproses, coba pilih ulang.');

                return;
            }

            if ($intern->avatar_path) {
                Storage::disk('public')->delete($intern->avatar_path);
            }

            $path = 'intern-avatars/' . $intern->id . '-' . now()->timestamp . '.jpg';
            Storage::disk('public')->put($path, $binary);
            $intern->avatar_path = $path;
        }

        $intern->dashboard_color = $this->dashboardColor !== '' ? $this->dashboardColor : null;
        $intern->save();

        $this->closeProfileModal();
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

        $pendingTasks = $intern
            ? $intern->tasks()->where('status', '!=', 'done')->latest()->take(3)->get()
            : collect();

        return view('livewire.home', [
            'user' => $user,
            'intern' => $intern,
            'now' => $now,
            'schedule' => $schedule,
            'todayAttendance' => $todayAttendance,
            'pendingTasks' => $pendingTasks,
            'pendingTasksCount' => $intern ? $intern->tasks()->where('status', '!=', 'done')->count() : 0,
            'recentJournals' => $journalsQuery
                ? (clone $journalsQuery)->latest('date')->take(5)->get()
                : collect(),
            'totalJournals' => $journalsQuery ? (clone $journalsQuery)->count() : 0,
            'lastJournalDate' => $journalsQuery
                ? (clone $journalsQuery)->max('date')
                : null,
        ]);
    }
}
