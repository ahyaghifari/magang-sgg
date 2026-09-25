<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Intern</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Seluruh peserta magang yang terlihat olehmu &middot; asal sekolah/institusi, periode magang,
            dan rekap ringkas jurnal, tugas, presensi, serta izin
        </p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem; margin-bottom:1.1rem;">
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Total Peserta</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $totalInterns }}</p>
        </div>
    </div>

    <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:1rem;">
        <label for="i-search" class="form-label">Cari</label>
        <input id="i-search" type="text" wire:model.live.debounce.400ms="search" class="form-input"
               placeholder="Nama peserta atau nama sekolah/institusi...">
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:0.9rem;">
        @forelse ($interns as $intern)
            @include('livewire.partials.intern-card', ['intern' => $intern])
        @empty
            <div class="surface-card" style="padding:2.75rem 1.15rem; text-align:center; grid-column:1/-1;">
                <i class="fa-regular fa-folder-open" style="font-size:1.6rem; color:var(--text-faint);"></i>
                <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                    Belum ada peserta yang cocok dengan pencarian.
                </p>
            </div>
        @endforelse
    </div>

    @if ($interns->hasPages())
        <div class="flex items-center justify-between" style="margin-top:1.25rem;">
            <button wire:click="previousPage" class="btn-ghost" @disabled($interns->onFirstPage())>
                <i class="fa-solid fa-chevron-left"></i> Sebelumnya
            </button>
            <span style="font-size:0.8rem; color:var(--text-muted);">
                Halaman {{ $interns->currentPage() }} dari {{ $interns->lastPage() }}
            </span>
            <button wire:click="nextPage" class="btn-ghost" @disabled(! $interns->hasMorePages())>
                Berikutnya <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    @endif

    @include('livewire.partials.intern-detail-modals')
</div>
