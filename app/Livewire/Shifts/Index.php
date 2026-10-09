<?php

namespace App\Livewire\Shifts;

use App\Models\InternShiftAssignment;
use App\Models\ShiftChangeRequest;
use App\Policies\InternShiftAssignmentPolicy;
use App\Services\Shift\ShiftAssignmentService;
use App\Services\Shift\ShiftChangeRequestService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Halaman "Jadwal Shift" milik intern: kalender bulanan shift / Libur dirinya (diisi Mentor).
 * Intern TIDAK mengubah jadwal langsung: ia memilih tanggal hari ini s.d. 30 hari ke depan yang
 * belum ada tap presensinya, lalu mengajukan perubahan per tanggal dengan satu alasan bersama
 * (submitBulkShiftRequests → ShiftChangeRequestService::submitEntries). Pengajuan diputuskan
 * Mentor-nya di halaman Pembimbing\Shifts.
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    /** Bulan yang ditampilkan, format Y-m. */
    #[Url]
    public string $month = '';

    /** Tanggal yang sedang dipilih (Y-m-d). */
    public array $selected = [];

    public function mount()
    {
        if (! auth()->user()->intern) {
            return $this->redirect(route('home'), navigate: true);
        }

        if (! $this->validMonth($this->month)) {
            $this->month = Carbon::today()->format('Y-m');
        }
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
        $intern = auth()->user()->intern;

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || ! Gate::allows('fillOwnDate', [InternShiftAssignment::class, $intern, $date])) {
            return;
        }

        $this->selected = in_array($date, $this->selected, true)
            ? array_values(array_diff($this->selected, [$date]))
            : [...$this->selected, $date];
    }

    /** Pilih cepat (satu kolom hari / satu baris minggu) — tanggal terkunci dibuang. */
    public function toggleDates(array $dates): void
    {
        $intern = auth()->user()->intern;

        if (count($dates) > ShiftAssignmentService::MAX_DATES) {
            return;
        }

        $dates = array_values(array_filter(array_unique($dates), fn ($d) => is_string($d)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
            && Gate::allows('fillOwnDate', [InternShiftAssignment::class, $intern, $d])));

        $this->selected = \App\Livewire\Pembimbing\Shifts::toggleSet($this->selected, $dates);
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /**
     * Ajukan perubahan shift untuk beberapa tanggal sekaligus (tiap tanggal boleh berbeda),
     * dengan satu alasan bersama. Intern tidak pernah mengubah jadwal langsung.
     *
     * @param  array<int, array{date: string, shift: string}>  $entries  shift: pagi|siang|malam|libur
     */
    public function submitBulkShiftRequests(array $entries, string $reason): void
    {
        try {
            $result = app(ShiftChangeRequestService::class)
                ->submitEntries(auth()->user(), auth()->user()->intern, $entries, $reason);
        } catch (ValidationException $e) {
            $this->dispatch('shift-toast', type: 'error', message: collect($e->errors())->flatten()->first());

            return;
        }

        $this->selected = [];
        $this->dispatch('shift-toast', type: 'success', message: count($result['requested']) . ' diajukan, ' . count($result['skipped']) . ' dilewati.');
        $this->dispatch('shift-bulk-saved');
    }

    public function cancelRequest(int $id): void
    {
        $request = ShiftChangeRequest::where('intern_id', auth()->user()->intern->id)->find($id);

        try {
            if (! $request) {
                throw ValidationException::withMessages(['request' => 'Pengajuan tidak ditemukan.']);
            }
            app(ShiftChangeRequestService::class)->cancel(auth()->user(), $request);
        } catch (ValidationException $e) {
            $this->dispatch('shift-toast', type: 'error', message: collect($e->errors())->flatten()->first());

            return;
        }

        $this->dispatch('shift-toast', type: 'success', message: 'Pengajuan dibatalkan.');
    }

    public function render()
    {
        $user = auth()->user();
        $intern = $user->intern;
        $service = app(ShiftAssignmentService::class);

        $start = $this->monthStart();
        $end = $start->copy()->endOfMonth();

        $entries = InternShiftAssignment::query()
            ->with(['shift', 'updater'])
            ->where('intern_id', $intern->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        // Grid kalender, minggu dimulai Senin; sel null = di luar bulan ini.
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

        $lastChange = $entries->sortByDesc('updated_at')->first();

        // Tanggal yang bisa dipilih bulan ini — aturan sama dengan Policy::fillOwnDate, dihitung sekali.
        $today = Carbon::today();
        $windowEnd = $today->copy()->addDays(InternShiftAssignmentPolicy::OWN_WINDOW_DAYS);
        $pickable = [];
        $from = $start->copy()->max($today)->copy();
        $to = $end->copy()->min($windowEnd)->copy();
        if ($from->lte($to)) {
            $tapped = InternShiftAssignmentPolicy::tappedDates($intern, $from->toDateString(), $to->toDateString());
            for ($d = $from; $d->lte($to); $d->addDay()) {
                if (! in_array($d->toDateString(), $tapped, true)) {
                    $pickable[$d->toDateString()] = true;
                }
            }
        }

        $requests = ShiftChangeRequest::query()
            ->with(['oldShift', 'requestedShift', 'decider'])
            ->where('intern_id', $intern->id)
            ->orderByRaw('status = ? desc', [ShiftChangeRequest::STATUS_PENDING])
            ->latest()
            ->limit(10)
            ->get();

        $pendingDates = ShiftChangeRequest::where('intern_id', $intern->id)
            ->where('status', ShiftChangeRequest::STATUS_PENDING)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        return view('livewire.shifts.index', [
            'intern' => $intern,
            'hasCompany' => (bool) $service->companyId($intern),
            'hasMentor' => (bool) $intern->mentor_id,
            'shifts' => $service->availableShifts($intern),
            // Pilihan pengajuan: Pagi/Siang/Malam (jam master perusahaan bila ada, selain itu bawaan) + Libur.
            'requestOptions' => \App\Support\ShiftTone::entryOptions($service->pickableShifts($intern)),
            'entries' => $entries,
            'weeks' => $weeks,
            'canPick' => fn (string $date) => isset($pickable[$date]),
            'pendingDates' => $pendingDates,
            'requests' => $requests,
            'monthLabel' => $start->copy()->locale('id')->translatedFormat('F Y'), // locale eksplisit: update Livewire menyetel ulang locale app (en)
            'today' => $today->toDateString(),
            'isCurrentMonth' => $start->isSameMonth($today),
            'lastChange' => $lastChange,
            'filledCount' => $entries->count(),
            'daysInMonth' => $start->daysInMonth,
            'windowDays' => InternShiftAssignmentPolicy::OWN_WINDOW_DAYS,
        ]);
    }
}
