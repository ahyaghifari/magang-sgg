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
                    <h2 style="font-size:1.05rem; font-weight:700;">Ajukan Izin</h2>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">
                        Pengajuan akan dikonfirmasi oleh pembimbing
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
                        {{-- Jenis izin --}}
                        <div style="margin-bottom:1.1rem;">
                            <span class="form-label">Jenis</span>
                            <div class="flex" style="gap:0.5rem; flex-wrap:wrap;">
                                @foreach (['izin' => 'Izin', 'sakit' => 'Sakit'] as $val => $label)
                                    <label class="flex items-center text-sm"
                                           style="gap:0.4rem; padding:0.5rem 0.85rem; border:1px solid {{ $type === $val ? 'var(--brand)' : 'var(--border)' }}; border-radius:10px; cursor:pointer;">
                                        <input type="radio" wire:model="type" value="{{ $val }}" style="accent-color:var(--brand);">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('type')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Rentang tanggal --}}
                        <div class="flex" style="gap:0.75rem; margin-bottom:1.1rem;">
                            <div style="flex:1;">
                                <label for="l-start" class="form-label">Mulai</label>
                                <input id="l-start" type="date" wire:model="startDate" class="form-input">
                                @error('startDate')
                                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div style="flex:1;">
                                <label for="l-end" class="form-label">Selesai</label>
                                <input id="l-end" type="date" wire:model="endDate" class="form-input">
                                @error('endDate')
                                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Alasan --}}
                        <div style="margin-bottom:1.1rem;">
                            <label for="l-reason" class="form-label">Alasan</label>
                            <textarea id="l-reason" wire:model="reason" rows="4" class="form-input"
                                      placeholder="Jelaskan alasan izin/sakit/cuti..."></textarea>
                            @error('reason')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Lampiran --}}
                        <div style="margin-bottom:1.25rem;">
                            <label for="l-attachment" class="form-label">Lampiran <span style="color:var(--text-faint); font-weight:400;">(opsional, contoh surat dokter)</span></label>
                            <input id="l-attachment" type="file" wire:model="attachment" class="file-box">
                            <div wire:loading wire:target="attachment" class="text-sm" style="color:var(--text-muted); margin-top:0.35rem;">
                                <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...
                            </div>
                            @error('attachment')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center" style="gap:0.65rem;">
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">
                                    <i class="fa-solid fa-paper-plane" style="margin-right:0.4rem;"></i>Kirim Pengajuan
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
