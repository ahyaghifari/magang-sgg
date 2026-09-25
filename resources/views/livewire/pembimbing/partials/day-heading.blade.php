{{-- Judul grup per hari di halaman Kegiatan. Wajib: heading (title, color), date (Carbon), first (bool). --}}
<div class="flex" style="align-items:center; gap:0.55rem; margin-top:{{ $first ? '0' : '0.9rem' }}; padding-bottom:0.3rem; border-bottom:2px solid {{ $heading['color'] }};">
    <span style="display:inline-block; width:0.6rem; height:0.6rem; border-radius:9999px; background:{{ $heading['color'] }}; flex-shrink:0;"></span>
    <span style="font-size:{{ $date->isToday() ? '1.05rem' : '0.95rem' }}; font-weight:800; color:{{ $heading['color'] }};">{{ $heading['title'] }}</span>
    <span class="text-sm" style="color:var(--text-muted);">{{ $date->translatedFormat('d F Y') }}</span>
</div>
