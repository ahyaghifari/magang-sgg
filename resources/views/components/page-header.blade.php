@props(['title'])

{{-- Header halaman portal (satu pola untuk semua halaman sidebar): judul di kiri, tombol aksi
     (slot "actions") sejajar di kanan; subjudul (slot "subtitle") opsional.
     Di HP, tombol turun ke baris bawah kalau tidak muat. Gaya: .page-header* di resources/css/app.css. --}}
<header class="page-header">
    <div class="page-header-main">
        <h1 class="portal-title">{{ $title }}</h1>
        @if (isset($subtitle) && trim((string) $subtitle) !== '')
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions) && trim((string) $actions) !== '')
        <div class="page-header-actions">{{ $actions }}</div>
    @endif
</header>
