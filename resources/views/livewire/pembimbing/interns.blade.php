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
            @php($mulai = $intern->tanggal_mulai ? \Illuminate\Support\Carbon::parse($intern->tanggal_mulai) : null)
            @php($selesai = $intern->tanggal_selesai ? \Illuminate\Support\Carbon::parse($intern->tanggal_selesai) : null)
            @php($sudahSelesai = $selesai && $selesai->isPast())
            <article class="surface-card" style="padding:1.1rem 1.15rem;">
                <div class="flex items-start justify-between" style="gap:0.6rem;">
                    <div style="min-width:0;">
                        <p style="font-weight:700; color:var(--text-heading);">
                            {{ $intern->nama }}
                            @if ($intern->nama_panggilan)
                                <span class="text-sm" style="font-weight:400; color:var(--text-muted);">({{ $intern->nama_panggilan }})</span>
                            @endif
                        </p>
                        <div class="flex" style="flex-wrap:wrap; gap:0.35rem; margin-top:0.4rem;">
                            @if ($intern->institusi)
                                <span class="badge badge-neutral"><i class="fa-solid fa-school"></i> {{ $intern->institusi->name }}</span>
                            @endif
                            @if ($intern->unit)
                                <span class="badge badge-neutral"><i class="fa-solid fa-people-group"></i> {{ $intern->unit->name }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="badge" style="{{ $sudahSelesai ? 'background:#f1f5f9; color:#64748b;' : 'background:#dcfce7; color:#15803d;' }} flex-shrink:0;">
                        <i class="fa-solid {{ $sudahSelesai ? 'fa-circle-check' : 'fa-circle-play' }}"></i>
                        {{ $sudahSelesai ? 'Selesai' : 'Berjalan' }}
                    </span>
                </div>

                <p class="text-sm" style="margin-top:0.7rem; color:var(--text-muted);">
                    <i class="fa-regular fa-calendar"></i>
                    @if ($mulai && $selesai)
                        {{ $mulai->translatedFormat('d F Y') }} &ndash; {{ $selesai->translatedFormat('d F Y') }}
                    @else
                        Periode magang belum diisi
                    @endif
                </p>

                <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0.6rem; margin-top:0.9rem; padding-top:0.85rem; border-top:1px solid var(--border-soft);">
                    <button type="button" wire:click="openJournals({{ $intern->id }})"
                            style="text-align:left; background:none; border:0; padding:0; cursor:pointer;">
                        <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.03em; color:var(--text-faint); font-weight:600;">
                            <i class="fa-solid fa-book"></i> Jurnal
                        </p>
                        <p style="margin-top:0.2rem; font-weight:700; color:var(--brand); text-decoration:underline;">{{ $intern->jurnal_count }}</p>
                    </button>
                    <button type="button" wire:click="openTasks({{ $intern->id }})"
                            style="text-align:left; background:none; border:0; padding:0; cursor:pointer;">
                        <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.03em; color:var(--text-faint); font-weight:600;">
                            <i class="fa-solid fa-clipboard-list"></i> Tugas
                        </p>
                        <p style="margin-top:0.2rem; font-weight:700; color:var(--brand); text-decoration:underline;">
                            {{ $intern->tugas_selesai_count }}<span style="font-weight:400; text-decoration:none; color:var(--text-muted);">/{{ $intern->tugas_total_count }} selesai</span>
                        </p>
                    </button>
                    <div>
                        <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.03em; color:var(--text-faint); font-weight:600;">
                            <i class="fa-solid fa-fingerprint"></i> Presensi
                        </p>
                        <p style="margin-top:0.2rem; font-size:0.82rem; color:var(--text-heading);">
                            <span style="color:#15803d; font-weight:700;">{{ $intern->presensi_hadir_count }}</span> hadir &middot;
                            <span style="color:#b45309; font-weight:700;">{{ $intern->presensi_telat_count }}</span> telat &middot;
                            <span style="color:#b91c1c; font-weight:700;">{{ $intern->presensi_absen_count }}</span> absen
                        </p>
                    </div>
                    <div>
                        <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.03em; color:var(--text-faint); font-weight:600;">
                            <i class="fa-solid fa-calendar-xmark"></i> Izin
                        </p>
                        <p style="margin-top:0.2rem; font-weight:700; color:var(--text-heading);">
                            {{ $intern->izin_approved_count }}<span style="font-weight:400; color:var(--text-muted);">/{{ $intern->izin_total_count }} disetujui</span>
                        </p>
                    </div>
                </div>

                <div style="margin-top:0.9rem; padding-top:0.85rem; border-top:1px solid var(--border-soft);">
                    <a href="{{ route('interns.certificate.view', $intern) }}" target="_blank" class="btn-ghost" style="width:100%; justify-content:center;">
                        <i class="fa-solid fa-award"></i> Sertifikat
                    </a>
                </div>
            </article>
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

    {{-- ===== Modal: semua jurnal peserta ===== --}}
    <div
        x-data
        x-show="$wire.viewingJournalsFor !== null"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$wire.viewingJournalsFor !== null && $wire.closeJournals()"
        style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
    >
        <div @click.outside="$wire.closeJournals()" x-show="$wire.viewingJournalsFor !== null" x-transition
             class="surface-card" style="width:100%; max-width:34rem; margin:auto; padding:0; max-height:85vh; display:flex; flex-direction:column;">
            <div class="flex items-center justify-between" style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft); flex-shrink:0;">
                <div>
                    <h2 style="font-size:1.05rem; font-weight:700;">Semua Jurnal</h2>
                    @if ($viewingJournalsIntern)
                        <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">{{ $viewingJournalsIntern->nama }} &middot; {{ $journalsList->count() }} jurnal</p>
                    @endif
                </div>
                <button type="button" wire:click="closeJournals" class="theme-toggle" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div style="padding:1.1rem 1.35rem; overflow-y:auto;">
                @if ($journalsList && $journalsList->isNotEmpty())
                    <div class="flex" style="flex-direction:column; gap:0.75rem;">
                        @foreach ($journalsList as $journal)
                            <div style="padding-bottom:0.75rem; border-bottom:1px solid var(--border-soft);">
                                <p class="text-sm" style="font-weight:700; color:var(--text-heading);">
                                    {{ \Illuminate\Support\Carbon::parse($journal->date)->translatedFormat('d F Y') }}
                                </p>
                                <p class="text-sm" style="margin-top:0.25rem; color:var(--text-body); white-space:pre-line;">{{ $journal->activity }}</p>
                                @if ($journal->attachments->isNotEmpty())
                                    <div class="flex" style="flex-wrap:wrap; gap:0.5rem; margin-top:0.55rem;">
                                        @foreach ($journal->attachments as $att)
                                            @if ($att->type === 'link')
                                                <a href="{{ $att->url }}" target="_blank" rel="noopener"
                                                   class="badge badge-neutral" style="text-decoration:none;">
                                                    <i class="fa-solid fa-link"></i>
                                                    {{ \Illuminate\Support\Str::limit($att->label ?: $att->url, 40) }}
                                                </a>
                                            @elseif ($att->type === 'document')
                                                <a href="{{ url('storage/' . $att->path) }}" target="_blank" rel="noopener"
                                                   class="badge badge-neutral" style="text-decoration:none;">
                                                    <i class="fa-regular fa-file-pdf" style="color:#dc2626;"></i>
                                                    {{ \Illuminate\Support\Str::limit($att->label ?: 'Dokumen PDF', 40) }}
                                                </a>
                                            @else
                                                <button type="button" onclick="openLightbox(@js(url('storage/' . $att->path)), @js($att->label))"
                                                        style="display:block; padding:0; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:none; cursor:zoom-in;">
                                                    <img src="{{ url('storage/' . $att->path) }}" alt="{{ $att->label }}"
                                                         style="width:72px; height:72px; object-fit:cover; display:block;">
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm" style="color:var(--text-muted); text-align:center; padding:1.5rem 0;">Belum ada jurnal.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== Modal: semua tugas peserta ===== --}}
    <div
        x-data
        x-show="$wire.viewingTasksFor !== null"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$wire.viewingTasksFor !== null && $wire.closeTasks()"
        style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
    >
        <div @click.outside="$wire.closeTasks()" x-show="$wire.viewingTasksFor !== null" x-transition
             class="surface-card" style="width:100%; max-width:34rem; margin:auto; padding:0; max-height:85vh; display:flex; flex-direction:column;">
            <div class="flex items-center justify-between" style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft); flex-shrink:0;">
                <div>
                    <h2 style="font-size:1.05rem; font-weight:700;">Semua Tugas</h2>
                    @if ($viewingTasksIntern)
                        <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">{{ $viewingTasksIntern->nama }} &middot; {{ $tasksList->count() }} tugas</p>
                    @endif
                </div>
                <button type="button" wire:click="closeTasks" class="theme-toggle" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div style="padding:1.1rem 1.35rem; overflow-y:auto;">
                @if ($tasksList && $tasksList->isNotEmpty())
                    <div class="flex" style="flex-direction:column; gap:0.75rem;">
                        @foreach ($tasksList as $task)
                            <div style="padding-bottom:0.75rem; border-bottom:1px solid var(--border-soft);">
                                <div class="flex items-center justify-between" style="gap:0.5rem;">
                                    <p class="text-sm" style="font-weight:700; color:var(--text-heading);">{{ $task->title }}</p>
                                    @if ($task->status === 'done')
                                        <span class="badge" style="background:#dcfce7; color:#15803d; flex-shrink:0;"><i class="fa-solid fa-circle-check"></i> Selesai</span>
                                    @elseif ($task->status === 'in_progress')
                                        <span class="badge" style="background:#fef3c7; color:#b45309; flex-shrink:0;"><i class="fa-solid fa-spinner"></i> Dikerjakan</span>
                                    @elseif ($task->status === 'rejected')
                                        <span class="badge" style="background:#fee2e2; color:#b91c1c; flex-shrink:0;"><i class="fa-solid fa-circle-xmark"></i> Ditolak</span>
                                    @else
                                        <span class="badge badge-neutral" style="flex-shrink:0;"><i class="fa-regular fa-circle"></i> Belum</span>
                                    @endif
                                </div>
                                @if ($task->description)
                                    <p class="text-sm" style="margin-top:0.25rem; color:var(--text-body); white-space:pre-line;">{{ $task->description }}</p>
                                @endif
                                <p class="text-sm" style="margin-top:0.35rem; color:var(--text-muted);">
                                    @if ($task->assignedBy)
                                        Diberikan oleh {{ $task->assignedBy->name }} &middot;
                                    @endif
                                    {{ $task->created_at->translatedFormat('d F Y, H:i') }}
                                    @if ($task->due_date)
                                        &middot; Tenggat {{ $task->due_date->translatedFormat('d F Y') }}
                                    @endif
                                </p>
                                @if ($task->completionPhotos->isNotEmpty())
                                    <div class="flex" style="flex-wrap:wrap; gap:0.5rem; margin-top:0.55rem;">
                                        @foreach ($task->completionPhotos as $photo)
                                            <button type="button" onclick="openLightbox(@js(url('storage/' . $photo->path)), @js('Bukti selesai: ' . $task->title))"
                                                    style="display:block; padding:0; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:none; cursor:zoom-in;">
                                                <img src="{{ url('storage/' . $photo->path) }}" alt="Bukti selesai: {{ $task->title }}"
                                                     style="width:72px; height:72px; object-fit:cover; display:block;">
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif ($task->status === 'done')
                                    <p class="text-sm" style="margin-top:0.4rem; color:var(--text-faint); font-style:italic;">Tidak ada foto bukti.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm" style="color:var(--text-muted); text-align:center; padding:1.5rem 0;">Belum ada tugas.</p>
                @endif
            </div>
        </div>
    </div>
</div>
