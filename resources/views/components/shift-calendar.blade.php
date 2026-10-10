{{--
    Kalender bulanan jadwal shift (reusable) — halaman peserta, halaman pembimbing/mentor/admin,
    dan Dashboard Pimpinan.

    Props:
      weeks    array minggu (Senin–Minggu), tiap sel Carbon atau null (di luar bulan)
      entries  koleksi InternShiftAssignment keyBy Y-m-d (dengan relasi shift & updater)
      selected array tanggal Y-m-d yang sedang dipilih
      today    Y-m-d
      readonly true = hanya menampilkan (tanpa pilih tanggal, tanpa ikon gembok)
      canPick  opsional fn(string $date): bool; default semua tanggal bisa dipilih bila tidak readonly.
      pending  array tanggal Y-m-d yang punya pengajuan perubahan shift menunggu (ikon jam pasir).
    Memanggil toggleDate('Y-m-d') di komponen Livewire induk. Bila tidak readonly, juga ada pilih
    cepat: ketuk nama hari (semua Senin, dst. di bulan ini) atau tombol di kiri baris (satu minggu)
    → toggleDates([...]) di induk — hanya tanggal yang bisa dipilih yang dikirim.
--}}
@props([
    'weeks' => [],
    'entries' => collect(),
    'selected' => [],
    'today' => null,
    'readonly' => false,
    'canPick' => null,
    'pending' => [],
])

@php
    $tone = \App\Support\ShiftTone::class;
    $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    $today ??= now()->toDateString();
    $fullDayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    // Pilih cepat: tanggal yang BISA dipilih per kolom hari & per baris minggu.
    $quickPick = ! $readonly;
    $isPickable = fn (string $date) => ! $readonly && ($canPick ? $canPick($date) : true);
    $colDates = array_fill(0, 7, []);
    $weekDates = [];
    foreach ($weeks as $w => $week) {
        $weekDates[$w] = [];
        foreach ($week as $i => $day) {
            if ($day && $isPickable($day->toDateString())) {
                $colDates[$i][] = $day->toDateString();
                $weekDates[$w][] = $day->toDateString();
            }
        }
    }
    $allSelected = fn (array $dates) => $dates !== [] && array_diff($dates, $selected) === [];
@endphp

<div {{ $attributes->merge(['class' => 'surface-card shift-cal']) }}>
    @if ($quickPick)
        <div class="shift-cal-grid has-week" style="margin-bottom:0.3rem;">
            <div aria-hidden="true"></div>
            @foreach ($dayNames as $i => $dn)
                <button type="button" wire:click="toggleDates(@js($colDates[$i]))" @disabled($colDates[$i] === [])
                        class="shift-cal-dow is-pick {{ $i >= 5 ? 'is-weekend' : '' }} {{ $allSelected($colDates[$i]) ? 'is-active' : '' }}"
                        title="Pilih semua hari {{ $fullDayNames[$i] }} bulan ini"
                        aria-label="Pilih semua hari {{ $fullDayNames[$i] }} bulan ini"
                        aria-pressed="{{ $allSelected($colDates[$i]) ? 'true' : 'false' }}">{{ $dn }}</button>
            @endforeach
        </div>
    @else
        <div class="shift-cal-grid" style="margin-bottom:0.3rem;" aria-hidden="true">
            @foreach ($dayNames as $i => $dn)
                <div class="shift-cal-dow {{ $i >= 5 ? 'is-weekend' : '' }}">{{ $dn }}</div>
            @endforeach
        </div>
    @endif

    <div style="display:flex; flex-direction:column; gap:0.4rem;">
        @foreach ($weeks as $w => $week)
            <div class="shift-cal-grid {{ $quickPick ? 'has-week' : '' }}">
                @if ($quickPick)
                    <button type="button" wire:click="toggleDates(@js($weekDates[$w]))" @disabled($weekDates[$w] === [])
                            class="shift-cal-week {{ $allSelected($weekDates[$w]) ? 'is-active' : '' }}"
                            title="Pilih satu minggu ini" aria-label="Pilih semua tanggal minggu ke-{{ $w + 1 }}"
                            aria-pressed="{{ $allSelected($weekDates[$w]) ? 'true' : 'false' }}">
                        <i class="fa-solid fa-angles-right" aria-hidden="true"></i>
                    </button>
                @endif
                @foreach ($week as $i => $day)
                    @if (! $day)
                        <div aria-hidden="true"></div>
                    @else
                        @php
                            $date = $day->toDateString();
                            $entry = $entries->get($date);
                            $type = $entry ? ($entry->off_day ? 'Libur' : ($entry->shift?->code ?? 'Shift')) : null;
                            $t = $tone::for($type);
                            $pickable = ! $readonly && ($canPick ? $canPick($date) : true);
                            $locked = ! $readonly && ! $pickable;
                            $isSelected = in_array($date, $selected, true);
                            $isToday = $date === $today;
                            $isPast = $date < $today;
                            $isPending = in_array($date, $pending, true);

                            $longLabel = $type === 'Libur' ? 'Libur' : ($entry?->shift ? $entry->shift->code . ' · ' . $tone::shortRange($entry->shift) : $type);
                            $shortLabel = $type ? mb_substr($type, 0, 1) : '';

                            $aria = $day->locale('id')->translatedFormat('l, j F Y')
                                . ($isToday ? ' (hari ini)' : '')
                                . ' — ' . ($entry ? $entry->label() : 'kosong, jam kerja biasa')
                                . ($entry?->updater ? '. Diubah oleh ' . $entry->updater->name . ', ' . $entry->updated_at->locale('id')->translatedFormat('j M Y H:i') : '')
                                . ($isPending ? '. Ada pengajuan perubahan menunggu' : '')
                                . ($isSelected ? '. Dipilih' : '')
                                . ($locked ? '. Terkunci' : '');

                            $classes = \Illuminate\Support\Arr::toCssClasses([
                                'shift-cell',
                                $t['class'] => (bool) $type,
                                'has-shift' => (bool) $type,
                                'is-weekend' => $i >= 5,
                                'is-today' => $isToday,
                                'is-past' => $isPast && ! $isToday,
                                'is-selected' => $isSelected,
                                'is-pickable' => $pickable,
                            ]);
                        @endphp

                        @if ($pickable)
                            <button type="button" wire:key="day-{{ $date }}" wire:click="toggleDate('{{ $date }}')"
                                    class="{{ $classes }}" title="{{ $aria }}" aria-label="{{ $aria }}" aria-pressed="{{ $isSelected ? 'true' : 'false' }}">
                        @else
                            <div wire:key="day-{{ $date }}" class="{{ $classes }}" title="{{ $aria }}" aria-label="{{ $aria }}" role="img">
                        @endif
                                <span class="shift-cell-day">{{ $day->day }}</span>

                                @if ($isSelected)
                                    <span class="shift-cell-corner"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
                                @elseif ($isPending)
                                    <span class="shift-cell-corner is-pending"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
                                @elseif ($locked)
                                    <span class="shift-cell-corner"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                                @endif

                                @if ($type)
                                    <span class="shift-cell-label">
                                        <i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i>
                                        <span class="lbl-long">{{ $longLabel }}</span>
                                        <span class="lbl-short">{{ $shortLabel }}</span>
                                    </span>
                                @else
                                    <span class="shift-cell-empty">Kosong</span>
                                @endif
                        @if ($pickable)
                            </button>
                        @else
                            </div>
                        @endif
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</div>
