{{--
    Header kalender jadwal shift: navigasi bulan, progres pengisian, ringkasan per shift,
    tombol "Hari ini" / "Pilih semua", badge "Hanya lihat", dan bantuan singkat.
    Memanggil previousMonth / nextMonth / thisMonth / selectAllOpen di komponen Livewire induk.

    Props: monthLabel, isCurrentMonth, entries (keyBy Y-m-d), daysInMonth, shifts,
           editable (boleh memilih tanggal), subtitle (opsional, mis. nama peserta).
--}}
@props([
    'monthLabel',
    'isCurrentMonth' => true,
    'entries' => collect(),
    'daysInMonth' => 30,
    'shifts' => collect(),
    'editable' => false,
    'subtitle' => null,
])

@php
    $tone = \App\Support\ShiftTone::class;

    // Ringkasan bulan ini: jumlah per jenis shift + Libur + Kosong.
    $counts = $entries->countBy(fn ($e) => $e->off_day ? 'Libur' : ($e->shift?->code ?? 'Shift'));
    $types = $shifts->pluck('code')->merge($counts->keys())->reject(fn ($c) => $c === 'Libur')->unique()->values();
    $filled = $entries->count();
    $empty = max(0, $daysInMonth - $filled);
    $percent = $daysInMonth ? (int) round($filled / $daysInMonth * 100) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'surface-card shift-head']) }}>
    <div class="flex items-center justify-between" style="gap:0.6rem;">
        <button type="button" wire:click="previousMonth" class="shift-nav-btn" aria-label="Bulan sebelumnya">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <div style="text-align:center; min-width:0; flex:1;">
            <p class="shift-month" aria-live="polite">{{ $monthLabel }}</p>
            @if ($subtitle)
                <p class="shift-muted-line" style="margin-top:0.1rem;">{{ $subtitle }}</p>
            @endif
        </div>
        <button type="button" wire:click="nextMonth" class="shift-nav-btn" aria-label="Bulan berikutnya">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Progres pengisian --}}
    <div style="margin-top:0.8rem;">
        <div class="flex items-center justify-between" style="gap:0.5rem; margin-bottom:0.3rem;">
            <span style="font-size:0.75rem; font-weight:600; color:var(--text-muted);">Terisi</span>
            <span style="font-size:0.75rem; font-weight:700; color:var(--text-heading); font-variant-numeric:tabular-nums;">{{ $filled }}/{{ $daysInMonth }} hari</span>
        </div>
        <div class="shift-progress" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $daysInMonth }}" aria-valuenow="{{ $filled }}"
             aria-label="{{ $filled }} dari {{ $daysInMonth }} hari sudah diisi">
            <span style="width:{{ $percent }}%;"></span>
        </div>
    </div>

    {{-- Ringkasan per jenis --}}
    <div class="flex" style="flex-wrap:wrap; gap:0.35rem; margin-top:0.65rem;">
        @foreach ($types as $type)
            @php($t = $tone::for($type))
            <span class="shift-chip {{ $t['class'] }}">
                <i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i> {{ $type }} {{ $counts->get($type, 0) }}
            </span>
        @endforeach
        @php($t = $tone::for('Libur'))
        <span class="shift-chip {{ $t['class'] }}"><i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i> Libur {{ $counts->get('Libur', 0) }}</span>
        <span class="shift-chip shift-chip-empty"><i class="fa-regular fa-square" aria-hidden="true"></i> Kosong {{ $empty }}</span>
    </div>

    {{-- Aksi kecil + bantuan --}}
    <div class="flex items-center" style="flex-wrap:wrap; gap:0.45rem; margin-top:0.7rem;">
        @unless ($isCurrentMonth)
            <button type="button" wire:click="thisMonth" class="shift-btn-sm">
                <i class="fa-solid fa-calendar-day" aria-hidden="true"></i> Hari ini
            </button>
        @endunless
        @if ($editable)
            <button type="button" wire:click="selectAllOpen" class="shift-btn-sm">
                <i class="fa-solid fa-check-double" aria-hidden="true"></i> Pilih semua tanggal
            </button>
        @else
            <span class="shift-readonly-badge"><i class="fa-solid fa-eye" aria-hidden="true"></i> Hanya lihat</span>
        @endif

        <details class="shift-help" style="margin-left:auto;">
            <summary aria-label="Bantuan"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Bantuan</summary>
            <div class="shift-help-body">
                @if ($editable)
                    Ketuk satu atau beberapa tanggal, lalu pilih <b>Pagi</b>, <b>Siang</b>, <b>Libur</b>, atau <b>Kosongkan</b> di panel bawah.
                    Tanggal yang sudah lewat juga bisa diubah — presensinya otomatis dihitung ulang.
                @else
                    Kalender ini hanya untuk dilihat. Jadwal diisi oleh pembimbing atau mentor peserta.
                @endif
                Tanggal kosong mengikuti jam kerja biasa perusahaan. Tahan/arahkan kursor ke tanggal untuk melihat siapa yang terakhir mengubah.
            </div>
        </details>
    </div>
</div>
