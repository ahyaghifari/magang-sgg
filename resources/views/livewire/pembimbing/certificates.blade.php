<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Sertifikat Intern</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Lihat/download sertifikat PKL peserta magang (halaman 2 berisi form penilaian/appraisal
            lengkap) &middot; tombol "Edit Penilaian" hanya tersedia untuk peserta yang kamu bimbing/mentori
            &middot; klik bintang untuk menilai tiap kriteria (1-5), Nilai Akhir &amp; Rating dihitung
            otomatis dari rata-ratanya
        </p>
    </div>

    {{-- ===== Daftar ===== --}}
    <div class="flex" style="flex-direction:column; gap:0.75rem;">
        @forelse ($interns as $intern)
            <article class="surface-card" style="padding:0.95rem 1.1rem;" x-data="{ showNilai: false }">
                <div class="flex items-center justify-between" style="gap:0.75rem; flex-wrap:wrap; margin-bottom:0.75rem;">
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
                            Nilai Akhir:
                            @if ($intern->nilai_akhir !== null)
                                <span style="font-weight:700; color:var(--brand);">{{ number_format($intern->nilai_akhir, 2) }}</span>
                                ({{ $intern->predikat() }})
                            @else
                                belum dinilai
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
                        @if (in_array($intern->id, $manageableInternIds, true))
                            <button type="button" @click="showNilai = !showNilai" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                                <i class="fa-solid fa-pen"></i> <span x-text="showNilai ? 'Tutup Penilaian' : 'Edit Penilaian'"></span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ===== 10 kriteria — terbagi 2 kategori, tiap kriteria dinilai lewat klik bintang
                     1-5 (sama seperti penilaian jurnal), auto-simpan begitu diklik. Cuma untuk intern
                     yang memang dibimbing/dimentori (manageableInterns()) — intern lain (yang cuma
                     bisa DILIHAT oleh Mentor) tidak punya tombol "Edit Penilaian" sama sekali, jadi
                     blok ini tidak akan pernah tampil untuk mereka. ===== --}}
                @if (in_array($intern->id, $manageableInternIds, true))
                    <div x-show="showNilai" x-cloak>
                        @foreach (['ATTITUDE', 'KNOWLEDGE & SKILL'] as $category)
                            <p class="text-sm" style="font-weight:700; color:var(--text-muted); margin:0.75rem 0 0.4rem; letter-spacing:0.03em;">{{ $category }}</p>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:0.6rem;">
                                @foreach ($criteria as $field => $c)
                                    @continue($c['category'] !== $category)
                                    @php($currentStars = $intern->{$field} !== null ? (float) $intern->{$field} : null)
                                    <div wire:key="crit-{{ $intern->id }}-{{ $field }}"
                                         style="border:1px solid var(--border); border-radius:10px; padding:0.6rem 0.75rem; background:var(--surface-alt);">
                                        <p class="text-sm" style="color:var(--text-muted); margin-bottom:0.3rem;" title="{{ $c['description'] }}">{{ $c['title'] }}</p>
                                        <div class="flex items-center" style="gap:0.25rem;">
                                            @for ($i = 1; $i <= 5; $i++)
                                                @php($fillPercent = $currentStars === null ? 0 : ($currentStars >= $i ? 100 : ($currentStars >= $i - 0.5 ? 50 : 0)))
                                                <span style="position:relative; display:inline-block; width:1.1rem; height:1.1rem; font-size:1.1rem; line-height:1;">
                                                    <i class="fa-regular fa-star" style="position:absolute; inset:0; color:var(--text-faint);"></i>
                                                    <span style="position:absolute; inset:0; overflow:hidden; width:{{ $fillPercent }}%; pointer-events:none;">
                                                        <i class="fa-solid fa-star" style="color:#f59e0b;"></i>
                                                    </span>
                                                    <button type="button" wire:click="rate({{ $intern->id }}, '{{ $field }}', {{ $i - 0.5 }})"
                                                            aria-label="{{ number_format($i - 0.5, 1) }} bintang"
                                                            style="position:absolute; left:0; top:0; width:50%; height:100%; background:none; border:0; padding:0; cursor:pointer;"></button>
                                                    <button type="button" wire:click="rate({{ $intern->id }}, '{{ $field }}', {{ $i }})"
                                                            aria-label="{{ $i }} bintang"
                                                            style="position:absolute; right:0; top:0; width:50%; height:100%; background:none; border:0; padding:0; cursor:pointer;"></button>
                                                </span>
                                            @endfor
                                            @if ($currentStars)
                                                <span class="text-sm" style="color:var(--text-muted); margin-left:0.3rem;">{{ number_format($currentStars, 1) }}</span>
                                                <button type="button" wire:click="clearRating({{ $intern->id }}, '{{ $field }}')" class="text-sm"
                                                        style="color:var(--text-muted); margin-left:0.3rem; text-decoration:underline;">
                                                    hapus
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    {{-- ===== Catatan penilaian — inline-edit ===== --}}
                    <div
                        x-show="showNilai"
                        x-cloak
                        x-data="{
                            editing: false,
                            val: @js($intern->catatan_penilaian ?? ''),
                            error: null,
                            saving: false,
                            save() {
                                this.saving = true;
                                this.error = null;
                                $wire.updateCatatan({{ $intern->id }}, this.val === '' ? null : this.val)
                                    .then((err) => {
                                        this.saving = false;
                                        if (err) { this.error = err; return; }
                                        this.editing = false;
                                    });
                            },
                        }"
                        style="margin-top:0.75rem; border:1px solid var(--border); border-radius:10px; padding:0.6rem 0.75rem; background:var(--surface-alt);"
                    >
                        <p class="text-sm" style="color:var(--text-muted); margin-bottom:0.25rem;">Catatan Penilaian</p>

                        <template x-if="!editing">
                            <button type="button" @click="editing = true; $nextTick(() => $refs.textarea?.focus())"
                                    style="background:none; border:none; padding:0; cursor:pointer; text-align:left; width:100%; color:var(--text-body); font-size:0.85rem;">
                                <span x-text="val === '' ? 'Belum ada catatan — klik untuk isi' : val" :style="val === '' ? 'color:var(--text-faint);' : ''"></span>
                                <i class="fa-solid fa-pen" style="font-size:0.6rem; opacity:0.45; margin-left:0.35rem;"></i>
                            </button>
                        </template>

                        <template x-if="editing">
                            <div>
                                <textarea x-model="val" x-ref="textarea" rows="2" class="form-input" style="margin-bottom:0.4rem;"></textarea>
                                <div class="flex items-center" style="gap:0.4rem;">
                                    <button type="button" @click="save()" :disabled="saving" class="btn-ghost" style="padding:0.35rem 0.7rem;">
                                        <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> Simpan
                                    </button>
                                    <button type="button" @click="editing = false" class="btn-ghost" style="padding:0.35rem 0.7rem;">Batal</button>
                                </div>
                            </div>
                        </template>

                        <p x-show="error" x-text="error" class="text-sm" style="color:#dc2626; margin-top:0.3rem;"></p>
                    </div>
                @endif
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
</div>
