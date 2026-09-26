{{-- Modal jurnal & tugas lengkap per peserta. Komponen induk harus memakai App\Livewire\Concerns\HasInternDetailModals. --}}
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
