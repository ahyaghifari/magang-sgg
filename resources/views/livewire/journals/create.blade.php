<div>
    <div
        x-data
        x-show="$wire.show"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$wire.show && $wire.close()"
        x-effect="document.body.style.overflow = $wire.show ? 'hidden' : ''"
        class="overlay-center"
        style="position:fixed; inset:0; z-index:50; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
    >
        <div
            @click.outside="$wire.close()"
            x-show="$wire.show"
            x-transition
            class="surface-card"
            style="width:100%; max-width:40rem; margin:auto; padding:0;"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between"
                 style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft);">
                <div>
                    <h2 style="font-size:1.05rem; font-weight:700;">Isi Jurnal Hari Ini</h2>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">
                        Catat kegiatan magangmu beserta lampiran pendukung
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
                        {{-- Tanggal --}}
                        <div style="margin-bottom:1.1rem;">
                            <span class="form-label">Tanggal</span>
                            <div class="form-input"
                                 style="max-width:20rem; display:flex; align-items:center; justify-content:space-between; gap:0.75rem; background:var(--surface-alt); cursor:not-allowed;">
                                <span>{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
                                <i class="fa-solid fa-lock" style="color:var(--text-faint); font-size:0.8rem;"></i>
                            </div>
                            @error('date')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Kegiatan --}}
                        <div style="margin-bottom:1.1rem;">
                            <label for="j-activity" class="form-label">Kegiatan <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                            <textarea id="j-activity" wire:model="activity" rows="6" class="form-input"
                                      placeholder="Tuliskan kegiatan yang kamu lakukan hari ini..."></textarea>
                            @error('activity')
                                <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Lampiran --}}
                        <div style="margin-bottom:1.25rem;">
                            <span class="form-label">Lampiran <span style="color:#dc2626; font-weight:600;">*</span> <span style="color:var(--text-faint); font-weight:400;">(wajib, minimal satu)</span></span>
                            <p class="text-sm" style="color:var(--text-faint); margin:0.15rem 0 0.6rem;">
                                Tambah "Foto" atau "PDF" dulu, lalu di kotak file-nya bisa pilih atau seret &amp; taruh
                                (drag-and-drop) lebih dari satu file sekaligus dari komputermu.
                            </p>

                            @error('items')
                                <p class="text-sm" style="color:#dc2626; margin-top:-0.1rem; margin-bottom:0.6rem;">
                                    <i class="fa-solid fa-circle-exclamation" style="margin-right:0.3rem;"></i>{{ $message }}
                                </p>
                            @enderror

                            {{-- Input kamera tersembunyi. Tombol "Kamera" mengklik ini langsung dalam
                                 gesture tap user, jadi kamera HP terbuka seketika (tanpa tap kedua). --}}
                            <input type="file" accept="image/*" capture="environment" x-ref="cameraInput"
                                   style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none;"
                                   x-on:change="
                                       const input = $event.target;
                                       const picked = input.files && input.files[0];
                                       input.value = '';
                                       if (!picked) return;
                                       let file = picked;
                                       if ($wire.compressImages) {
                                           try { file = await window.compressImageFile(picked); } catch (e) { file = picked; }
                                       }
                                       await $wire.addItem('photo', true);
                                       const idx = (($wire.items && $wire.items.length) || 1) - 1;
                                       $wire.upload('items.' + idx + '.file', file, () => {}, () => {}, () => {});
                                   ">

                            <div class="flex" style="flex-wrap:wrap; gap:0.5rem; margin-bottom:0.7rem;">
                                <button type="button" wire:click="addItem('photo')" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                                    <i class="fa-regular fa-image"></i> Foto
                                </button>
                                <button type="button" x-on:click="$refs.cameraInput.click()" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                                    <i class="fa-solid fa-camera"></i> Kamera
                                </button>
                                <button type="button" wire:click="addItem('document')" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                                    <i class="fa-regular fa-file-pdf"></i> PDF
                                </button>
                                <button type="button" wire:click="addItem('link')" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                                    <i class="fa-solid fa-link"></i> Link
                                </button>
                            </div>

                            <label class="flex items-center text-sm"
                                   style="gap:0.5rem; color:var(--text-muted); margin-bottom:0.85rem; cursor:pointer;">
                                <input type="checkbox" wire:model.live="compressImages"
                                       style="width:1rem; height:1rem; border-radius:4px; accent-color:var(--brand); flex-shrink:0;">
                                Kecilkan ukuran foto otomatis sebelum diunggah
                            </label>

                            @forelse ($items as $i => $item)
                                <div wire:key="item-{{ $i }}"
                                     style="border:1px solid var(--border); border-radius:12px; padding:0.9rem; margin-bottom:0.65rem; background:var(--surface-alt);">
                                    <div class="flex items-center justify-between" style="margin-bottom:0.6rem;">
                                        <span class="badge badge-neutral">
                                            @if ($item['type'] === 'photo' && ($item['capture'] ?? false))
                                                <i class="fa-solid fa-camera"></i> Kamera
                                            @elseif ($item['type'] === 'photo')
                                                <i class="fa-regular fa-image"></i> Foto
                                            @elseif ($item['type'] === 'document')
                                                <i class="fa-regular fa-file-pdf"></i> PDF
                                            @else
                                                <i class="fa-solid fa-link"></i> Link
                                            @endif
                                        </span>
                                        <button type="button" wire:click="removeItem({{ $i }})"
                                                style="color:var(--text-muted); font-size:0.85rem;" aria-label="Hapus lampiran">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <input type="text" wire:model="items.{{ $i }}.label" class="form-input"
                                           style="margin-bottom:0.55rem;" placeholder="Keterangan (opsional)">

                                    @if ($item['type'] === 'link')
                                        <input type="url" wire:model="items.{{ $i }}.url" class="form-input"
                                               placeholder="https://...">
                                    @elseif ($item['type'] === 'document')
                                        {{-- Bisa pilih/drop lebih dari satu PDF sekaligus — tiap file tambahan otomatis
                                             jadi lampiran baru, sama seperti pola multi-foto di bawah. --}}
                                        <input type="file" class="file-box" accept="application/pdf" multiple
                                               wire:key="document-input-{{ $i }}"
                                               x-data
                                               x-on:change="
                                                   const inp = $event.target;
                                                   const files = Array.from(inp.files || []);
                                                   if (!files.length) return;
                                                   inp.disabled = true;
                                                   for (let j = 0; j < files.length; j++) {
                                                       let path = 'items.{{ $i }}.file';
                                                       if (j > 0) {
                                                           await $wire.addItem('document');
                                                           const idx = (($wire.items && $wire.items.length) || 1) - 1;
                                                           path = 'items.' + idx + '.file';
                                                       }
                                                       await new Promise((resolve) => $wire.upload(path, files[j], resolve, resolve, () => {}));
                                                   }
                                                   inp.disabled = false;
                                                   inp.value = '';
                                               ">
                                        <p class="text-sm" style="color:var(--text-faint); margin-top:0.3rem;">
                                            Maks. 10MB per file, boleh lebih dari satu.
                                        </p>
                                        <div wire:loading wire:target="items.{{ $i }}.file"
                                             class="text-sm" style="color:var(--text-muted); margin-top:0.35rem;">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...
                                        </div>
                                        @if ($item['file'])
                                            <p class="text-sm" wire:loading.remove wire:target="items.{{ $i }}.file"
                                               style="color:var(--brand-success); margin-top:0.3rem; overflow-wrap:anywhere;">
                                                <i class="fa-solid fa-circle-check" style="margin-right:0.3rem;"></i>{{ $item['file']->getClientOriginalName() }}
                                                <span style="color:var(--text-faint);">— pilih lagi untuk mengganti.</span>
                                            </p>
                                        @endif
                                    @else
                                        {{-- Foto: bila opsi "kecilkan otomatis" aktif, kompres dulu di browser lalu unggah manual.
                                             capture="environment" → di HP langsung buka kamera belakang. --}}
                                        <input type="file" class="file-box" accept="image/*"
                                               @if ($item['capture'] ?? false) capture="environment" @else multiple @endif
                                               wire:key="photo-input-{{ $i }}"
                                               x-data
                                               x-on:change="
                                                   const inp = $event.target;
                                                   const files = Array.from(inp.files || []);
                                                   if (!files.length) return;
                                                   inp.disabled = true;
                                                   for (let j = 0; j < files.length; j++) {
                                                       let f = files[j];
                                                       if ($wire.compressImages) {
                                                           try { f = await window.compressImageFile(f); } catch (e) {}
                                                       }
                                                       let path = 'items.{{ $i }}.file';
                                                       if (j > 0) {
                                                           await $wire.addItem('photo');
                                                           const idx = (($wire.items && $wire.items.length) || 1) - 1;
                                                           path = 'items.' + idx + '.file';
                                                       }
                                                       await new Promise((resolve) => $wire.upload(path, f, resolve, resolve, () => {}));
                                                   }
                                                   inp.disabled = false;
                                                   inp.value = '';
                                               ">
                                        <div wire:loading wire:target="items.{{ $i }}.file"
                                             class="text-sm" style="color:var(--text-muted); margin-top:0.35rem;">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...
                                        </div>
                                        @if ($item['file'])
                                            <p class="text-sm" wire:loading.remove wire:target="items.{{ $i }}.file"
                                               style="color:var(--brand-success); margin-top:0.3rem; overflow-wrap:anywhere;">
                                                <i class="fa-solid fa-circle-check" style="margin-right:0.3rem;"></i>{{ $item['file']->getClientOriginalName() }}
                                                <span style="color:var(--text-faint);">— pilih/ambil lagi untuk mengganti.</span>
                                            </p>
                                        @elseif ($compressImages)
                                            <p class="text-sm" style="color:var(--text-faint); margin-top:0.3rem;">
                                                <i class="fa-solid fa-wand-magic-sparkles" style="margin-right:0.3rem;"></i>Foto akan dikecilkan otomatis.
                                            </p>
                                        @endif
                                    @endif

                                    @error("items.$i.file")
                                        <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                                    @enderror
                                    @error("items.$i.url")
                                        <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                                    @enderror
                                </div>
                            @empty
                                <p class="text-sm" style="color:var(--text-muted);">Belum ada lampiran. Tambahkan minimal satu.</p>
                            @endforelse
                        </div>

                        <div class="flex items-center" style="gap:0.65rem;">
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">
                                    <i class="fa-solid fa-floppy-disk" style="margin-right:0.4rem;"></i>Simpan Jurnal
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
