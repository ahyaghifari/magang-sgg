{{-- Badge shift di rekap presensi. Wajib: $shiftLabel (array{type, label}|null). Warna: .shift-tone-* (ikut mode gelap). --}}
@if (! empty($shiftLabel))
    @php($t = \App\Support\ShiftTone::for($shiftLabel['type']))
    <span class="badge shift-badge {{ $t['class'] }}" title="Jadwal shift">
        <i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i> {{ $shiftLabel['label'] }}
    </span>
@endif
