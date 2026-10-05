<?php

namespace App\Livewire\Shifts;

use App\Models\InternShiftAssignment;
use App\Services\Shift\ShiftAssignmentService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Halaman "Jadwal Shift" milik intern — BACA-SAJA: kalender bulanan shift / Libur dirinya.
 * Yang mengisi & mengoreksi jadwal hanya Pembimbing (binaannya) dan Mentor (dampingannya),
 * lewat halaman Pembimbing\Shifts (aturan di InternShiftAssignmentPolicy).
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    /** Bulan yang ditampilkan, format Y-m. */
    #[Url]
    public string $month = '';

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
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonth()->format('Y-m');
    }

    public function thisMonth(): void
    {
        $this->month = Carbon::today()->format('Y-m');
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

        return view('livewire.shifts.index', [
            'intern' => $intern,
            'hasCompany' => (bool) $service->companyId($intern),
            'shifts' => $service->availableShifts($intern),
            'selected' => [],
            'entries' => $entries,
            'weeks' => $weeks,
            'monthLabel' => $start->copy()->locale('id')->translatedFormat('F Y'), // locale eksplisit: update Livewire menyetel ulang locale app (en)
            'today' => Carbon::today()->toDateString(),
            'isCurrentMonth' => $start->isSameMonth(Carbon::today()),
            'lastChange' => $lastChange,
            'filledCount' => $entries->count(),
            'daysInMonth' => $start->daysInMonth,
        ]);
    }
}
