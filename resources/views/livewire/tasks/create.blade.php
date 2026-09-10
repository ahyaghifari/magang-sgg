<div>
    <div
        x-data
        x-show="$wire.show"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$wire.show && $wire.close()"
        x-effect="document.body.style.overflow = $wire.show ? 'hidden' : ''"
        style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
    >
        <div
            @click.outside="$wire.close()"
            x-show="$wire.show"
            x-transition
            class="surface-card"
            style="width:100%; max-width:32rem; margin:auto; padding:0;"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between"
                 style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft);">
                <div>
                    <h2 style="font-size:1.05rem; font-weight:700;">Catat Tugas</h2>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">
                        Untuk tugas yang disampaikan pembimbing secara langsung (lisan)
                    </p>
                </div>
                <button type="button" wire:click="close" class="theme-toggle" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div style="padding:1.35rem;">
                @unless ($hasIntern)
                    <div class="flex items-center" style="gap:0.85rem;">
                        <i class="fa-solid fa-circle-info" style="color:#d97706; font-size:1.1rem;"></i>
                        <p class="text-sm" style="color:var(--text-body);">
                            Akun kamu belum terhubung dengan data <strong>Intern</strong>.
                            Hubungi admin untuk membuatkan data magang terlebih dahulu.
                        </p>
                    </div>
                @else
                    <form wire:submit="save">
                        {{-- Judul --}}
                        <div style="margin-bottom:1.1rem;">
                            <label for="t-title" class="form-label">Judul Tugas</label>
                            <input id="t-title" type="text" wire:model="title" class="form-input"
                                   placeholder="Contoh: Buat laporan mingguan unit IT">
                            @error('title')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Keterangan --}}
                        <div style="margin-bottom:1.1rem;">
                            <label for="t-description" class="form-label">Keterangan <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                            <textarea id="t-description" wire:model="description" rows="4" class="form-input"
                                      placeholder="Detail tugas, sesuai yang disampaikan pembimbing..."></textarea>
                            @error('description')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Pembimbing pemberi tugas --}}
                        <div style="margin-bottom:1.1rem;">
                            <label for="t-assigned" class="form-label">Diberikan oleh <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                            <select id="t-assigned" wire:model="assignedBy" class="form-input">
                                <option value="">Pilih pembimbing...</option>
                                @foreach ($pembimbings as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            @error('assignedBy')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tenggat --}}
                        <div style="margin-bottom:1.25rem;">
                            <label for="t-due" class="form-label">Tenggat <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                            <input id="t-due" type="date" wire:model="dueDate" class="form-input" style="max-width:14rem;">
                            @error('dueDate')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center" style="gap:0.65rem;">
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">
                                    <i class="fa-solid fa-floppy-disk" style="margin-right:0.4rem;"></i>Simpan Tugas
                                </span>
                                <span wire:loading wire:target="save">
                                    <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.4rem;"></i>Menyimpan...
                                </span>
                            </button>
                            <button type="button" wire:click="close" class="btn-ghost">Batal</button>
                        </div>
                    </form>
                @endunless
            </div>
        </div>
    </div>
</div>
