<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Sertifikat Intern</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Lihat/download sertifikat PKL intern yang dibimbing/dimentori olehmu &middot; nama, nama panggilan,
            dan tanggal magang boleh diedit di sini (ikut memperbarui isi sertifikatnya)
        </p>
    </div>

    {{-- ===== Daftar ===== --}}
    <div class="flex" style="flex-direction:column; gap:0.6rem;">
        @forelse ($interns as $intern)
            <article class="surface-card flex items-center justify-between"
                     style="padding:0.85rem 1.1rem; gap:0.75rem; flex-wrap:wrap;">
                <div style="min-width:0;">
                    <p style="font-size:0.9rem; font-weight:700; color:var(--text-heading);">
                        {{ $intern->nama }}
                        @if ($intern->nama_panggilan)
                            <span class="text-sm" style="font-weight:400; color:var(--text-muted);">({{ $intern->nama_panggilan }})</span>
                        @endif
                        @if ($intern->unit)
                            <span class="badge badge-neutral" style="margin-left:0.35rem;">
                                <i class="fa-solid fa-people-group"></i> {{ $intern->unit->name }}
                            </span>
                        @endif
                    </p>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.15rem;">
                        Magang:
                        @if ($intern->tanggal_mulai || $intern->tanggal_selesai)
                            {{ $intern->tanggal_mulai?->translatedFormat('d M Y') ?? '-' }}
                            s.d.
                            {{ $intern->tanggal_selesai?->translatedFormat('d M Y') ?? '-' }}
                        @else
                            belum diisi
                        @endif
                    </p>
                </div>
                <div class="flex items-center" style="gap:0.4rem; flex-wrap:wrap;">
                    <a href="{{ route('interns.certificate.view', $intern) }}" target="_blank" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                        <i class="fa-solid fa-eye"></i> Lihat
                    </a>
                    <a href="{{ route('interns.certificate', $intern) }}" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                    <button type="button" wire:click="edit({{ $intern->id }})" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                        <i class="fa-solid fa-pen"></i> Edit
                    </button>
                </div>
            </article>
        @empty
            <div class="surface-card" style="padding:2.5rem 1.15rem; text-align:center;">
                <i class="fa-regular fa-id-card" style="font-size:1.6rem; color:var(--text-faint);"></i>
                <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                    Belum ada intern yang ditugaskan ke kamu.
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

    {{-- ===== Modal edit ===== --}}
    <div
        x-data
        x-show="$wire.showEdit"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$wire.closeEdit()"
        style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
    >
        <div @click.outside="$wire.closeEdit()" class="surface-card" style="width:100%; max-width:28rem; margin:auto; padding:0;">
            <div class="flex items-center justify-between"
                 style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft);">
                <h2 style="font-size:1.05rem; font-weight:700;">Edit Data Sertifikat</h2>
                <button type="button" wire:click="closeEdit" class="theme-toggle" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form wire:submit="save" style="padding:1.35rem;">
                <div style="margin-bottom:1rem;">
                    <label for="e-nama" class="form-label">Nama</label>
                    <input id="e-nama" type="text" wire:model="nama" class="form-input">
                    @error('nama')
                        <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div style="margin-bottom:1rem;">
                    <label for="e-panggilan" class="form-label">Nama Panggilan</label>
                    <input id="e-panggilan" type="text" wire:model="namaPanggilan" class="form-input" placeholder="Opsional">
                    @error('namaPanggilan')
                        <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex" style="gap:0.75rem; margin-bottom:1.25rem;">
                    <div style="flex:1;">
                        <label for="e-mulai" class="form-label">Magang Mulai</label>
                        <input id="e-mulai" type="date" wire:model="tanggalMulai" class="form-input">
                        @error('tanggalMulai')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div style="flex:1;">
                        <label for="e-selesai" class="form-label">Magang Selesai</label>
                        <input id="e-selesai" type="date" wire:model="tanggalSelesai" class="form-input">
                        @error('tanggalSelesai')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center" style="gap:0.65rem;">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">
                            <i class="fa-solid fa-floppy-disk" style="margin-right:0.4rem;"></i>Simpan
                        </span>
                        <span wire:loading wire:target="save">
                            <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.4rem;"></i>Menyimpan...
                        </span>
                    </button>
                    <button type="button" wire:click="closeEdit" class="btn-ghost">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
