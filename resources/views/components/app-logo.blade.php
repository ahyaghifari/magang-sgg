@props(['alt' => 'Internship Syifa Global Group'])
{{-- Ikon web (tanpa tulisan) — sumbernya di App\Support\Brand::logoUrl(). Ukurannya persegi;
     beri alt="" bila nama web sudah ditulis sebagai teks di sebelahnya (hindari dibaca dua kali). --}}
<img src="{{ \App\Support\Brand::logoUrl() }}" alt="{{ $alt }}" width="512" height="512" {{ $attributes }}>
