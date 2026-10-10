<?php

namespace App\Livewire\Pembimbing;

use App\Models\Intern;
use App\Models\InternShiftAssignment;
use App\Models\ShiftChangeRequest;
use App\Services\Shift\ShiftAssignmentService;
use App\Services\Shift\ShiftChangeRequestService;
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
 * - Mengubah: hanya Mentor (dampingannya) — InternShiftAssignmentPolicy::
 *   correctAnyDate — boleh tanggal mana saja termasuk lampau; koreksi tanggal hari ini/lampau otomatis
 *   menghitung ulang presensi (di ShiftAssignmentService). Admin & Pimpinan baca-saja.
 * - Pengajuan perubahan dari intern: daftar "menunggu" tampil untuk Mentor intern tsb, yang
 *   menyetujui/menolak (ShiftChangeRequestService::decide).
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

    /** Catatan keputusan mentor per pengajuan, [id => teks]. */
    public array $decisionNotes = [];

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

    /**
     * Pilih cepat (satu kolom hari / satu baris minggu): kalau semua sudah terpilih → dilepas,
     * selain itu ditambahkan ke pilihan.
     */
    public function toggleDates(array $dates): void
    {
        if (! $this->canCorrect($this->intern())) {
            return;
        }

        $dates = array_values(array_unique(array_filter($dates, fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d))));
        $this->selected = self::toggleSet($this->selected, $dates);
    }

    /** @return array<int, string> */
    public static function toggleSet(array $selected, array $dates): array
    {
        if ($dates === []) {
            return $selected;
        }

        return array_diff($dates, $selected) === []
            ? array_values(array_diff($selected, $dates))
            : array_values(array_unique([...$selected, ...$dates]));
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

    /** @param  int|string|null  $shiftId  id master shift atau jenis baku (Pagi/Siang/Malam) yang belum punya master shift */
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

    /**
     * Mode "Atur Per Tanggal": tiap tanggal diberi shift berbeda lalu disimpan sekaligus.
     * Intern yang diatur = intern yang sedang dipilih di halaman (tidak diterima dari klien).
     *
     * @param  array<int, array{date: string, shift: string}>  $entries  shift: pagi|siang|malam|libur
     */
    public function saveBulkShiftSchedule(array $entries): void
    {
        $intern = $this->intern();

        try {
            if (! $intern) {
                throw ValidationException::withMessages(['entries' => 'Pilih peserta dulu.']);
            }
            $result = app(ShiftAssignmentService::class)->applyEntries(auth()->user(), $intern, $entries);
        } catch (ValidationException $e) {
            $this->dispatch('shift-toast', type: 'error', message: collect($e->errors())->flatten()->first());

            return;
        }

        $message = count($result['saved']) . ' tersimpan, ' . count($result['skipped']) . ' dilewati.';
        if ($result['past_changed'] !== []) {
            $message .= ' Presensi ' . count($result['past_changed']) . ' tanggal yang sudah lewat dihitung ulang.';
        }

        $this->selected = [];
        $this->dispatch('shift-toast', type: 'success', message: $message);
        $this->dispatch('shift-bulk-saved');
    }

    /** Mentor menyetujui / menolak pengajuan perubahan shift intern dampingannya. */
    public function decide(int $id, bool $approve): void
    {
        $request = ShiftChangeRequest::with('intern')->find($id);

        try {
            if (! $request) {
                throw ValidationException::withMessages(['request' => 'Pengajuan tidak ditemukan.']);
            }
            app(ShiftChangeRequestService::class)->decide(auth()->user(), $request, $approve, $this->decisionNotes[$id] ?? null);
        } catch (ValidationException $e) {
            $this->dispatch('shift-toast', type: 'error', message: collect($e->errors())->flatten()->first());

            return;
        }

        unset($this->decisionNotes[$id]);
        $this->dispatch('shift-toast', type: 'success', message: $approve
            ? 'Pengajuan disetujui — jadwal ' . $request->intern->nama . ' sudah diperbarui.'
            : 'Pengajuan ditolak.');
    }

    /** Pengajuan menunggu dari intern dampingan mentor ini (kosong untuk peran lain). */
    protected function pendingRequestsForMentor()
    {
        $user = auth()->user();

        if (! $user->isMentor() || $user->isSuperAdmin() || $user->isViewingAsIntern()) {
            return collect();
        }

        return ShiftChangeRequest::query()
            ->with(['intern:id,nama,avatar_path,mentor_id', 'oldShift', 'requestedShift'])
            ->where('status', ShiftChangeRequest::STATUS_PENDING)
            ->whereHas('intern', fn ($q) => $q->where('mentor_id', $user->id))
            ->orderBy('date')
            ->get();
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

        // Pilihan intern: yang memakai jadwal shift (dipilih admin) tampil di grup pertama.
        $interns = $this->internsQuery()
            ->with('unit:id,name')
            ->orderByDesc('interns.uses_shift')
            ->orderBy('nama')
            ->get(['interns.id', 'interns.nama', 'interns.unit_id', 'interns.avatar_path', 'interns.uses_shift']);

        return view('livewire.pembimbing.shifts', [
            'intern' => $intern,
            'interns' => $interns,
            'canCorrect' => $canCorrect,
            'hasCompany' => $intern ? (bool) $service->companyId($intern) : true,
            // Mentor yang berhak: Pagi, Siang & Malam selalu bisa dipilih (lihat pickableShifts).
            'shifts' => $intern ? ($canCorrect ? $service->pickableShifts($intern) : $service->availableShifts($intern)) : collect(),
            'entries' => $entries,
            'weeks' => $weeks,
            'pendingRequests' => $this->embedded ? collect() : $this->pendingRequestsForMentor(),
            'pendingDates' => $intern
                ? ShiftChangeRequest::where('intern_id', $intern->id)
                    ->where('status', ShiftChangeRequest::STATUS_PENDING)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->pluck('date')->map(fn ($d) => $d->toDateString())->all()
                : [],
            'monthLabel' => $start->copy()->locale('id')->translatedFormat('F Y'), // locale eksplisit: update Livewire menyetel ulang locale app (en)
            'today' => Carbon::today()->toDateString(),
            'isCurrentMonth' => $start->isSameMonth(Carbon::today()),
            'lastChange' => $entries->sortByDesc('updated_at')->first(),
            'filledCount' => $entries->count(),
            'daysInMonth' => $start->daysInMonth,
        ]);
    }
}
