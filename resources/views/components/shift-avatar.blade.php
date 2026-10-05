{{-- Foto/inisial intern non-interaktif (aman di dalam tombol) untuk komponen jadwal shift. --}}
@props(['intern' => null, 'large' => false])

@php
    $nama = $intern?->nama ?? '?';
    $inisial = \Illuminate\Support\Str::of($nama)->explode(' ')->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
@endphp

<span {{ $attributes->class(['shift-avatar', 'is-lg' => $large]) }} aria-hidden="true">
    @if ($intern?->avatar_path)
        <img src="{{ url('storage/' . $intern->avatar_path) }}" alt="" loading="lazy">
    @else
        {{ $inisial }}
    @endif
</span>
