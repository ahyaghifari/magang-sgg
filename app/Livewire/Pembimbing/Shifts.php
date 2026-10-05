<?php

namespace App\Livewire\Pembimbing;

use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Services\Shift\ShiftAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * "Jadwal Shift Intern" untuk Pembimbing, Mentor dan Admin (portal): pilih seorang intern lalu
 * lihat kalender shift bulanannya. Pimpinan melihatnya sebagai bagian Dashboard Pimpinan
 * (komponen ini disematkan dengan embedded=true), bukan halaman terpisah.
 * - Daftar intern: Pimpinan semua; lainnya User::visibleInterns() (Admin semua, Pembimbing
 *   binaannya, Mentor semua).
 * - Mengubah: hanya Pembimbing (binaannya) dan Mentor (dampingannya) — InternShiftAssignmentPolicy::
 *   correctAnyDate — boleh tanggal mana saja termasuk lampau; koreksi tanggal hari ini/lampau otomatis
 *   menghitung ulang presensi (di ShiftAssignmentService). Admin & Pimpinan baca-saja.
 */
#[Layout('components.layouts.app')]
class Shifts extends Component
{
    #[Url(as: 'intern')]
    public string $internId = '';

    /** Bulan yang ditampilkan, format Y-m. */
    #[Url]
    public string $month = '';

    /** Tanggal yang sedang dipilih (Y-m-d). */
    public array $selected = [];

    /** true = disematkan di Dashboard Pimpinan (tanpa judul halaman). */
    public bool $embedded = false;

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }

        // Pimpinan melihat jadwal shift di dashboard-nya, bukan halaman terpisah.
        if (auth()->user()->isPimpinan() && ! $this->embedded) {
            return $this->redirect(route('pimpinan.dashboard'), navigate: true);
        }

        if (! $this->validMonth($this->month)) {
            $this->month = Carbon::today()->format('Y-m');
        }

        if ($this->internId !== '' && ! $this->intern()) {
            $this->internId = '';
        }
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && ($user->isPortalMentor() || $user->isPimpinan());
    }

    protected function internsQuery()
    {
        $user = auth()->user();

        return $user->isPimpinan() ? Intern::query() : $user->visibleInterns();
    }

    /** Intern yang dipilih — hanya kalau memang boleh dilihat user ini. */
    protected function intern(): ?Intern
    {
        if ($this->internId === '' || ! ctype_digit($this->internId)) {
            return null;
        }

        $intern = $this->internsQuery()->whereKey((int) $this->internId)->with('unit')->first();

        return $intern && Gate::allows('viewSchedule', [InternShiftAssignment::class, $intern]) ? $intern : null;
    }

    protected function canCorrect(?Intern $intern): bool
    {
        return $intern && Gate::allows('correctAnyDate', [InternShiftAssignment::class, $intern]);
    }

    public function updatedInternId(): void
    {
        $this->selected = [];
    }

    protected function validMonth(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value);
    }

    protected function monthStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', ($this->validMonth($this->month) ? $this->month : Carbon::today()->format('Y-m')) . '-01')->startOfDay();
    }

    public function previousMonth(): void
    {
        $this->month = $this->monthStart()->subMonth()->format('Y-m');
        $this->selected = [];
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonth()->format('Y-m');
        $this->selected = [];
    }

    public function thisMonth(): void
    {
        $this->month = Carbon::today()->format('Y-m');
        $this->selected = [];
    }

    public function toggleDate(string $date): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! $this->canCorrect($this->intern())) {
            return;
        }

        $this->selected = in_array($date, $this->selected, true)
            ? array_values(array_diff($this->selected, [$date]))
            : [...$this->selected, $date];
    }

    public function selectAllOpen(): void
    {
        if (! $this->canCorrect($this->intern())) {
            return;
        }

        $start = $this->monthStart();
        $dates = [];
        for ($d = $start->copy(); $d->month === $start->month; $d->addDay()) {
            $dates[] = $d->toDateString();
        }
        $this->selected = $dates;
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /** @param  int|string|null  $shiftId  id master shift atau jenis baku (Pagi/Siang) yang belum punya master shift */
    public function apply(string $action, int|string|null $shiftId = null): void
    {
        $intern = $this->intern();

        if (! $this->canCorrect($intern)) {
            $this->dispatch('shift-toast', type: 'error', message: 'Kamu tidak punya akses untuk mengubah jadwal intern ini.');

            return;
        }

        try {
            $result = app(ShiftAssignmentService::class)->apply(auth()->user(), $intern, $this->selected, $action, $shiftId);
        } catch (ValidationException $e) {
            $this->dispatch('shift-toast', type: 'error', message: collect($e->errors())->flatten()->first());

            return;
        }

        $done = count($result['saved']) + count($result['cleared']);
        $message = match ($action) {
            ShiftAssignmentService::ACTION_OFF => "{$done} tanggal ditandai Libur.",
            ShiftAssignmentService::ACTION_CLEAR => "Isian {$done} tanggal dikosongkan.",
            default => "Shift diterapkan ke {$done} tanggal.",
        };
        if ($result['past_changed'] !== []) {
            $message .= ' Presensi ' . count($result['past_changed']) . ' tanggal yang sudah lewat dihitung ulang.';
        }
        if ($result['locked'] !== []) {
            $message .= ' ' . count($result['locked']) . ' tanggal dilewati.';
        }

        $this->selected = [];
        $this->dispatch('shift-toast', type: 'success', message: $message);
    }

    public function render()
    {
        $user = auth()->user();
        $intern = $this->intern();
        $service = app(ShiftAssignmentService::class);
        $canCorrect = $this->canCorrect($intern);

        $start = $this->monthStart();
        $end = $start->copy()->endOfMonth();

        $entries = $intern
            ? InternShiftAssignment::query()
                ->with(['shift', 'updater'])
                ->where('intern_id', $intern->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->keyBy(fn ($a) => $a->date->toDateString())
            : collect();

        $weeks = [];
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $last = $end->copy()->endOfWeek(Carbon::SUNDAY);
        while ($cursor->lte($last)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $cursor->month === $start->month ? $cursor->copy() : null;
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        // Pilihan intern: yang sudah terdaftar di shift / punya jadwal tampil di grup pertama.
        $interns = $this->internsQuery()
            ->with('unit:id,name')
            ->withExists(['shifts as has_shift', 'shiftAssignments as has_schedule'])
            ->orderBy('nama')
            ->get(['interns.id', 'interns.nama', 'interns.unit_id', 'interns.avatar_path']);

        return view('livewire.pembimbing.shifts', [
            'intern' => $intern,
            'interns' => $interns,
            'canCorrect' => $canCorrect,
            'hasCompany' => $intern ? (bool) $service->companyId($intern) : true,
            // Pembimbing/Mentor yang berhak: Pagi & Siang selalu bisa dipilih (lihat pickableShifts).
            'shifts' => $intern ? ($canCorrect ? $service->pickableShifts($intern) : $service->availableShifts($intern)) : collect(),
            'entries' => $entries,
            'weeks' => $weeks,
            'monthLabel' => $start->copy()->locale('id')->translatedFormat('F Y'), // locale eksplisit: update Livewire menyetel ulang locale app (en)
            'today' => Carbon::today()->toDateString(),
            'isCurrentMonth' => $start->isSameMonth(Carbon::today()),
            'lastChange' => $entries->sortByDesc('updated_at')->first(),
            'filledCount' => $entries->count(),
            'daysInMonth' => $start->daysInMonth,
        ]);
    }
}
