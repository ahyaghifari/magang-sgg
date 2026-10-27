<div>
    <x-page-header title="Sertifikat Intern" />

    {{-- ===== Daftar ===== --}}
    <div class="flex" style="flex-direction:column; gap:0.75rem;">
        @forelse ($interns as $intern)
            <article class="surface-card" style="padding:0.95rem 1.1rem;" x-data="{ showNilai: false }">
                <div class="flex items-center justify-between" style="gap:0.75rem; flex-wrap:wrap; margin-bottom:0.75rem;">
                    <div style="min-width:0;">
                        <p style="font-size:0.9rem; font-weight:700; color:var(--text-heading);">
                            <x-intern-avatar :intern="$intern" size="2.2rem" style="margin-right:0.45rem;" />
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
                        {{-- Rincian nilai per penilai — Nilai Akhir di atas adalah rata-rata yang sudah masuk. --}}
                        <p class="text-sm" style="margin-top:0.3rem; display:flex; gap:0.35rem; flex-wrap:wrap;">
                            @foreach ($roles as $roleKey => $roleLabel)
                                @php($avg = $intern->appraisals->firstWhere('role', $roleKey)?->average())
                                <span class="badge {{ $avg !== null ? 'badge-neutral' : '' }}" style="{{ $avg === null ? 'color:var(--text-faint); border:1px dashed var(--border);' : '' }}">
                                    {{ $roleLabel }}: {{ $avg !== null ? number_format($avg, 2) : 'belum' }}
                                </span>
                            @endforeach
                        </p>
                    </div>
                    <div class="flex items-center" style="gap:0.4rem; flex-wrap:wrap;">
                        <a href="{{ route('interns.certificate.view', $intern) }}" target="_blank" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                            <i class="fa-solid fa-eye"></i> Lihat
                        </a>
                        <a href="{{ route('interns.certificate', $intern) }}" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                        {{-- Editor: semua yang boleh melihat bisa membuka; teks hanya bisa diedit untuk
                             intern yang boleh dikelola (lihat App\Support\CertificateAccess). --}}
                        <a href="{{ route('interns.certificate.editor', $intern) }}" target="_blank" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                            <i class="fa-solid fa-pen-ruler"></i> Buka Editor
                        </a>
                        @if (in_array($intern->id, $manageableInternIds, true))
                            {{-- "Isi" selama penilaian milik user ini (role-nya) belum ada satu pun nilai; setelahnya "Edit". --}}
                            @php($belumKuNilai = $intern->appraisals->firstWhere('role', $myRole)?->average() === null)
                            <button type="button" @click="showNilai = !showNilai" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                                <i class="fa-solid fa-pen"></i> <span x-text="showNilai ? 'Tutup Penilaian' : '{{ $belumKuNilai ? 'Isi Penilaian' : 'Edit Penilaian' }}'"></span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ===== 10 kriteria — terbagi 2 kategori, tiap kriteria dinilai lewat input angka
                     1-5 (desimal boleh, mis. 4,5), auto-simpan saat input selesai diisi. Cuma untuk intern
                     yang boleh dinilai user ini (appraisableInterns(): semua intern untuk semua role selain Intern). ===== --}}
                @if (in_array($intern->id, $manageableInternIds, true))
                    @php($mine = $intern->appraisals->firstWhere('role', $myRole))
                    <div x-show="showNilai" x-cloak>
                        <p class="text-sm" style="color:var(--text-muted); margin-top:0.5rem;">
                            Kamu menilai sebagai <strong>{{ $roles[$myRole] }}</strong> — nilai ini dirata-rata dengan penilaian lainnya.
                        </p>
                        @foreach (['ATTITUDE', 'KNOWLEDGE & SKILL'] as $category)
                            <p class="text-sm" style="font-weight:700; color:var(--text-muted); margin:0.75rem 0 0.4rem; letter-spacing:0.03em;">{{ $category }}</p>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:0.6rem;">
                                @foreach ($criteria as $field => $c)
                                    @continue($c['category'] !== $category)
                                    @php($currentStars = $mine?->{$field} !== null ? (float) $mine->{$field} : null)
                                    <div wire:key="crit-{{ $intern->id }}-{{ $field }}"
                                         style="border:1px solid var(--border); border-radius:10px; padding:0.6rem 0.75rem; background:var(--surface-alt);">
                                        <p class="text-sm" style="font-weight:600; color:var(--text-heading); margin-bottom:0.1rem;">{{ $c['title'] }}</p>
                                        <p style="font-size:0.72rem; line-height:1.35; color:var(--text-muted); margin-bottom:0.45rem;">{{ $c['description'] }}</p>
                                        {{-- Input desimal bebas (4,5 / 3.75), simpan saat blur/Enter; kosong = hapus.
                                             Server mengembalikan nilai ternormalisasi (batas 1-5) untuk ditampilkan lagi. --}}
                                        <div class="flex items-center" style="gap:0.5rem;"
                                             x-data="{ val: @js($currentStars !== null ? rtrim(rtrim(number_format($currentStars, 2, ',', ''), '0'), ',') : '') }">
                                            <input type="text" inputmode="decimal" x-model="val" class="form-input"
                                                   placeholder="1 - 5" autocomplete="off"
                                                   style="width:5.5rem; text-align:center; font-variant-numeric:tabular-nums;"
                                                   @change="val = await $wire.rate({{ $intern->id }}, '{{ $field }}', val)"
                                                   @keydown.enter.prevent="$event.target.blur()">
                                            <span style="font-size:0.75rem; color:var(--text-faint);" x-text="val === '' ? 'belum dinilai' : '/ 5'"></span>
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
                            val: @js($mine?->catatan ?? ''),
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
        <div class="pager" style="margin-top:1.25rem;">
            <button wire:click="previousPage" class="btn-ghost" @disabled($interns->onFirstPage())>
                <i class="fa-solid fa-chevron-left"></i> Sebelumnya
            </button>
            <span class="pager-info">
                Halaman {{ $interns->currentPage() }} dari {{ $interns->lastPage() }}
            </span>
            <button wire:click="nextPage" class="btn-ghost" @disabled(! $interns->hasMorePages())>
                Berikutnya <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    @endif
</div>
