{{--
    Legenda kalender jadwal shift dalam bentuk chip berwarna.
    Props: shifts (koleksi Shift), showLock (tampilkan chip "Terkunci").
--}}
@props(['shifts' => collect(), 'showLock' => false])

@php($tone = \App\Support\ShiftTone::class)

<div {{ $attributes->merge(['class' => 'flex', 'style' => 'flex-wrap:wrap; gap:0.4rem;']) }} aria-label="Keterangan warna kalender">
    @foreach ($shifts as $shift)
        @php($t = $tone::for($shift->code))
        <span class="shift-chip {{ $t['class'] }}">
            <i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i>
            {{ $shift->code }} · {{ $tone::shortRange($shift) }}
        </span>
    @endforeach
    @php($t = $tone::for('Libur'))
    <span class="shift-chip {{ $t['class'] }}"><i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i> Libur</span>
    <span class="shift-chip shift-chip-empty"><i class="fa-regular fa-square" aria-hidden="true"></i> Kosong = jam kerja biasa</span>
    <span class="shift-chip"><span class="shift-chip-today-dot" aria-hidden="true"></span> Hari ini</span>
    @if ($showLock)
        <span class="shift-chip"><i class="fa-solid fa-lock" aria-hidden="true"></i> Terkunci</span>
    @endif
</div>
