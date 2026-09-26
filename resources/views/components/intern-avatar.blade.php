{{--
    Foto profil intern (avatar_path, diunggah intern sendiri dari Beranda) untuk halaman
    pembimbing/mentor/pimpinan. Kalau ada foto: bisa diklik untuk diperbesar di lightbox
    global (lihat photo-lightbox.blade.php). Kalau belum ada: inisial nama (maks. 2 huruf).
--}}
@props(['intern' => null, 'size' => '2rem'])

@php
    $nama = $intern?->nama ?? '?';
    $inisial = \Illuminate\Support\Str::of($nama)->explode(' ')->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
    $style = "width:{$size}; height:{$size}; border-radius:9999px; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; vertical-align:middle;";
@endphp

@if ($intern?->avatar_path)
    <button type="button" onclick="openLightbox(@js(url('storage/' . $intern->avatar_path)), @js('Foto profil ' . $nama))"
            {{ $attributes->merge(['style' => $style . ' padding:0; border:0; background:none; cursor:zoom-in;']) }}
            aria-label="Lihat foto profil {{ $nama }}">
        <img src="{{ url('storage/' . $intern->avatar_path) }}" alt="Foto profil {{ $nama }}" loading="lazy"
             style="width:100%; height:100%; border-radius:9999px; object-fit:cover; border:1px solid var(--border);">
    </button>
@else
    <span {{ $attributes->merge(['style' => $style . ' background:var(--surface-alt); color:var(--text-muted); font-weight:700; font-size:calc(' . $size . ' * 0.38);']) }}
          aria-hidden="true">{{ $inisial }}</span>
@endif
