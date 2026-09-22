<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Sertifikat Intern</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Lihat/download sertifikat PKL peserta magang (halaman 2 berisi form penilaian/appraisal
            lengkap) &middot; tombol "Edit Data" hanya tersedia untuk peserta yang kamu bimbing/mentori
            &middot; klik nama, tanggal, atau salah satu nilai untuk mengubahnya langsung (nilai skala
            1-4, Nilai Akhir &amp; Rating dihitung otomatis dari rata-ratanya)
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
                                <i class="fa-solid fa-pen"></i> <span x-text="showNilai ? 'Tutup Edit' : 'Edit Data'"></span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- ===== 10 kriteria — terbagi 2 kategori, masing-masing bisa diedit sendiri-sendiri =====
                     Cuma untuk intern yang memang dibimbing/dimentori (manageableInterns()) — intern lain
                     (yang cuma bisa DILIHAT oleh Mentor) tidak punya tombol "Edit Penilaian" sama sekali,
                     jadi blok ini tidak akan pernah tampil untuk mereka. --}}
                @if (in_array($intern->id, $manageableInternIds, true))
                <div x-show="showNilai" x-cloak>
                    {{-- ===== Identitas — nama, nama panggilan, tanggal mulai/selesai — inline-edit
                         satu-satu sama seperti kriteria penilaian di bawah. ===== --}}
                    <p class="text-sm" style="font-weight:700; color:var(--text-muted); margin:0 0 0.4rem; letter-spacing:0.03em;">IDENTITAS</p>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:0.6rem; margin-bottom:0.75rem;">
                        @foreach ([
                            ['field' => 'nama', 'label' => 'Nama', 'type' => 'text'],
                            ['field' => 'nama_panggilan', 'label' => 'Nama Panggilan', 'type' => 'text'],
                            ['field' => 'tanggal_mulai', 'label' => 'Tanggal Mulai', 'type' => 'date'],
                            ['field' => 'tanggal_selesai', 'label' => 'Tanggal Selesai', 'type' => 'date'],
                        ] as $idf)
                            @php
                                $field = $idf['field'];
                                $raw = $intern->{$field};
                                $rawVal = $raw instanceof \Illuminate\Support\Carbon ? $raw->toDateString() : ($raw ?? '');
                            @endphp
                            <div
                                x-data="{
                                    editing: false,
                                    val: @js($rawVal),
                                    error: null,
                                    saving: false,
                                    save() {
                                        this.saving = true;
                                        this.error = null;
                                        $wire.updateField({{ $intern->id }}, '{{ $field }}', this.val === '' ? null : this.val)
                                            .then((err) => {
                                                this.saving = false;
                                                if (err) { this.error = err; return; }
                                                this.editing = false;
                                            });
                                    },
                                }"
                                style="border:1px solid var(--border); border-radius:10px; padding:0.6rem 0.75rem; background:var(--surface-alt);"
                            >
                                <p class="text-sm" style="color:var(--text-muted); margin-bottom:0.25rem;">{{ $idf['label'] }}</p>

                                <template x-if="!editing">
                                    <button type="button" @click="editing = true; $nextTick(() => $refs.input?.focus())"
                                            style="background:none; border:none; padding:0; cursor:pointer; text-align:left; width:100%; font-weight:700; color:var(--text-heading); font-size:0.9rem;">
                                        <span x-text="val === '' ? '—' : val"></span>
                                        <i class="fa-solid fa-pen" style="font-size:0.6rem; opacity:0.45; margin-left:0.35rem;"></i>
                                    </button>
                                </template>

                                <template x-if="editing">
                                    <div class="flex items-center" style="gap:0.3rem;">
                                        <input type="{{ $idf['type'] }}" x-model="val" x-ref="input"
                                               @keydown.enter="save()" @keydown.escape="editing = false"
                                               class="form-input" style="padding:0.3rem 0.5rem;">
                                        <button type="button" @click="save()" :disabled="saving"
                                                class="btn-ghost" style="padding:0.3rem 0.55rem; flex-shrink:0;" aria-label="Simpan">
                                            <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                                        </button>
                                        <button type="button" @click="editing = false" class="btn-ghost" style="padding:0.3rem 0.55rem; flex-shrink:0;" aria-label="Batal">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </template>

                                <p x-show="error" x-text="error" class="text-sm" style="color:#dc2626; margin-top:0.3rem;"></p>
                            </div>
                        @endforeach
                    </div>

                    @foreach (['ATTITUDE', 'KNOWLEDGE & SKILL'] as $category)
                        <p class="text-sm" style="font-weight:700; color:var(--text-muted); margin:0.75rem 0 0.4rem; letter-spacing:0.03em;">{{ $category }}</p>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:0.6rem;">
                            @foreach ($criteria as $field => $c)
                                @continue($c['category'] !== $category)
                                <div
                                    x-data="{
                                        editing: false,
                                        val: '{{ $intern->{$field} ?? '' }}',
                                        committed: '{{ $intern->{$field} ?? '' }}',
                                        error: null,
                                        saving: false,
                                        save() {
                                            if (this.val === this.committed) { this.editing = false; return; }
                                            this.saving = true;
                                            this.error = null;
                                            $wire.updateField({{ $intern->id }}, '{{ $field }}', this.val === '' ? null : this.val)
                                                .then((err) => {
                                                    this.saving = false;
                                                    if (err) { this.error = err; return; }
                                                    this.committed = this.val;
                                                    this.editing = false;
                                                });
                                        },
                                        cancel() {
                                            this.val = this.committed;
                                            this.editing = false;
                                        },
                                    }"
                                    style="border:1px solid var(--border); border-radius:10px; padding:0.6rem 0.75rem; background:var(--surface-alt);"
                                >
                                    <p class="text-sm" style="color:var(--text-muted); margin-bottom:0.25rem;" title="{{ $c['description'] }}">{{ $c['title'] }}</p>

                                    <template x-if="!editing">
                                        <button type="button" @click="editing = true; $nextTick(() => $refs.input?.focus())"
                                                style="background:none; border:none; padding:0; cursor:pointer; font-weight:700; color:var(--text-heading); font-size:1rem;">
                                            <span x-text="val === '' ? '—' : val"></span>
                                            <i class="fa-solid fa-pen" style="font-size:0.6rem; opacity:0.45; margin-left:0.35rem;"></i>
                                        </button>
                                    </template>

                                    {{-- Satu input yang menyesuaikan sendiri: klik untuk lihat pilihan cepat 1-4
                                         (datalist bawaan browser), atau ketik langsung angka lain (mis. 3.75) —
                                         tidak perlu tombol centang, auto-simpan begitu Enter atau pindah fokus.
                                         cancel() (Escape/tombol X) mengembalikan val ke nilai tersimpan terakhir
                                         SEBELUM editing dimatikan, supaya blur yang menyusul tidak ikut menyimpan
                                         perubahan yang sengaja dibatalkan (save() no-op kalau val === committed). --}}
                                    <template x-if="editing">
                                        <div class="flex items-center" style="gap:0.3rem;">
                                            <input type="number" min="1" max="4" step="0.01" x-model="val" x-ref="input"
                                                   list="crit-opts-{{ $intern->id }}-{{ $field }}"
                                                   @keydown.enter="save()" @blur="save()" @keydown.escape="cancel()"
                                                   class="form-input" style="width:5rem; padding:0.3rem 0.5rem;">
                                            <datalist id="crit-opts-{{ $intern->id }}-{{ $field }}">
                                                <option value="1"></option>
                                                <option value="2"></option>
                                                <option value="3"></option>
                                                <option value="4"></option>
                                            </datalist>
                                            <i x-show="saving" class="fa-solid fa-spinner fa-spin" style="color:var(--text-muted);"></i>
                                            <button type="button" @click="cancel()" x-show="!saving" class="btn-ghost" style="padding:0.3rem 0.55rem;" aria-label="Tutup">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </template>

                                    <p x-show="error" x-text="error" class="text-sm" style="color:#dc2626; margin-top:0.3rem;"></p>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                {{-- ===== Catatan penilaian — juga inline-edit ===== --}}
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
                            $wire.updateField({{ $intern->id }}, 'catatan_penilaian', this.val === '' ? null : this.val)
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
