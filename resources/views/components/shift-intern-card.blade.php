{{-- Kartu identitas ringkas peserta di atas kalender: foto/inisial, nama, unit, periode magang. --}}
@props(['intern'])

@php
    $fmt = fn ($d) => $d ? $d->locale('id')->translatedFormat('j M Y') : null;
    $periode = $intern->tanggal_mulai || $intern->tanggal_selesai
        ? ($fmt($intern->tanggal_mulai) ?? '?') . ' – ' . ($fmt($intern->tanggal_selesai) ?? '?')
        : 'Periode belum diisi';
@endphp

<div {{ $attributes->merge(['class' => 'surface-card flex items-center', 'style' => 'gap:0.8rem; padding:0.8rem 1rem;']) }}>
    <x-shift-avatar :intern="$intern" large />
    <div style="min-width:0; flex:1;">
        <p style="font-weight:800; color:var(--text-heading); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $intern->nama }}</p>
        <p class="shift-muted-line">
            <i class="fa-solid fa-people-group" aria-hidden="true"></i> {{ $intern->unit?->name ?? 'Unit belum diisi' }}
        </p>
        <p class="shift-muted-line">
            <i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $periode }}
        </p>
    </div>
    {{ $slot }}
</div>
