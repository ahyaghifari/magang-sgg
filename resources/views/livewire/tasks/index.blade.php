<div>
    <div class="flex items-center justify-between" style="gap:1rem; margin-bottom:1.4rem;">
        <div>
            <h1 class="portal-title">Tugas</h1>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
                Tugas dari pembimbing — baik yang diberikan lewat web maupun yang disampaikan langsung (lisan)
            </p>
        </div>
        @if ($intern)
            <button type="button" wire:click="$dispatch('open-task-modal')" class="btn-primary" style="flex-shrink:0;">
                <i class="fa-solid fa-plus"></i>
                <span>Catat Tugas</span>
            </button>
        @endif
    </div>

    <div x-data="{ show: false }" x-cloak
         x-on:task-saved.window="show = true; setTimeout(() => show = false, 4000)"
         x-show="show" x-transition
         class="surface-card flex items-center"
         style="gap:0.7rem; padding:0.8rem 1rem; margin-bottom:1rem; border-color:#a7f3d0;">
        <i class="fa-solid fa-circle-check" style="color:var(--brand-success);"></i>
        <span class="text-sm" style="color:var(--text-body);">Tugas berhasil dicatat.</span>
    </div>

    @if (! $intern)
        <div class="surface-card flex items-center" style="gap:0.85rem; padding:1rem 1.15rem;">
            <i class="fa-solid fa-circle-info" style="color:#d97706; font-size:1.1rem;"></i>
            <p class="text-sm" style="color:var(--text-body);">
                Akun kamu belum terhubung dengan data <strong>Intern</strong>.
                Hubungi admin terlebih dahulu.
            </p>
        </div>
    @else
        {{-- ===== Filter status ===== --}}
        <div class="flex" style="flex-wrap:wrap; gap:0.5rem; margin-bottom:1rem;">
            <button type="button" wire:click="$set('status', '')" class="badge {{ $status === '' ? 'badge-neutral' : 'badge-neutral' }}"
                    style="cursor:pointer; border:1px solid {{ $status === '' ? 'var(--brand)' : 'var(--border)' }};">Semua</button>
            <button type="button" wire:click="$set('status', 'pending')" class="badge badge-neutral"
                    style="cursor:pointer; border:1px solid {{ $status === 'pending' ? 'var(--brand)' : 'var(--border)' }};">Belum dikerjakan</button>
            <button type="button" wire:click="$set('status', 'in_progress')" class="badge badge-neutral"
                    style="cursor:pointer; border:1px solid {{ $status === 'in_progress' ? 'var(--brand)' : 'var(--border)' }};">Dikerjakan</button>
            <button type="button" wire:click="$set('status', 'done')" class="badge badge-neutral"
                    style="cursor:pointer; border:1px solid {{ $status === 'done' ? 'var(--brand)' : 'var(--border)' }};">Selesai</button>
        </div>

        {{-- ===== Filter tanggal ===== --}}
        <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:1rem; display:flex; flex-wrap:wrap; align-items:flex-end; gap:0.75rem;">
            <div>
                <label for="t-from" class="form-label">Dari tanggal</label>
                <input id="t-from" type="date" wire:model.live="dateFrom" class="form-input" style="max-width:12rem;">
            </div>
            <div>
                <label for="t-to" class="form-label">Sampai tanggal</label>
                <input id="t-to" type="date" wire:model.live="dateTo" class="form-input" style="max-width:12rem;">
            </div>
            @if ($dateFrom !== '' || $dateTo !== '')
                <button type="button" wire:click="resetDateFilter" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                    <i class="fa-solid fa-xmark"></i> Reset
                </button>
            @endif
        </div>

        <div class="flex" style="flex-direction:column; gap:0.85rem;">
            @forelse ($tasks as $task)
                <article class="surface-card" style="padding:1.1rem 1.15rem;">
                    <div class="flex items-center justify-between" style="gap:0.75rem; flex-wrap:wrap;">
                        <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap;">
                            <span style="font-weight:700; color:var(--text-heading);">{{ $task->title }}</span>
                            @if ($task->status === 'done')
                                <span class="badge" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-circle-check"></i> Selesai</span>
                            @elseif ($task->status === 'in_progress')
                                <span class="badge" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-spinner"></i> Dikerjakan</span>
                            @else
                                <span class="badge badge-neutral"><i class="fa-regular fa-circle"></i> Belum dikerjakan</span>
                            @endif
                            <span class="badge badge-neutral">
                                @if ($task->source === 'web')
                                    <i class="fa-solid fa-globe"></i> Dari pembimbing
                                @else
                                    <i class="fa-solid fa-comment"></i> Lisan
                                @endif
                            </span>
                        </div>
                        @if ($task->due_date)
                            <span class="text-sm" style="color:var(--text-muted); flex-shrink:0;">
                                <i class="fa-regular fa-calendar"></i> Tenggat {{ \Illuminate\Support\Carbon::parse($task->due_date)->translatedFormat('d F Y') }}
                            </span>
                        @endif
                    </div>

                    @if ($task->description)
                        <p class="text-sm" style="margin-top:0.55rem; color:var(--text-body); white-space:pre-line;">{{ $task->description }}</p>
                    @endif

                    @if ($task->assignedBy)
                        <p class="text-sm" style="margin-top:0.5rem; color:var(--text-muted);">
                            <i class="fa-solid fa-user"></i> Diberikan oleh {{ $task->assignedBy->name }}
                        </p>
                    @endif

                    @if ($task->status === 'done' && $task->completion_photo_path)
                        <div style="margin-top:0.75rem;">
                            <a href="{{ url('storage/' . $task->completion_photo_path) }}" target="_blank" rel="noopener"
                               style="display:inline-block; border-radius:10px; overflow:hidden; border:1px solid var(--border);">
                                <img src="{{ url('storage/' . $task->completion_photo_path) }}" alt="Bukti selesai"
                                     style="width:84px; height:84px; object-fit:cover; display:block;">
                            </a>
                            <p class="text-sm" style="margin-top:0.3rem; color:var(--text-faint);">
                                <i class="fa-regular fa-image"></i> Foto bukti pengerjaan
                            </p>
                        </div>
                    @endif

                    <div class="flex items-center" style="gap:0.5rem; margin-top:0.9rem; padding-top:0.7rem; border-top:1px solid var(--border-soft); flex-wrap:wrap;">
                        @if ($task->status !== 'in_progress' && $task->status !== 'done')
                            <button type="button" wire:click="markStatus({{ $task->id }}, 'in_progress')" class="btn-ghost" style="padding:0.4rem 0.75rem;">
                                <i class="fa-solid fa-play"></i> Mulai
                            </button>
                        @endif
                        @if ($task->status !== 'done')
                            <button type="button" wire:click="openComplete({{ $task->id }})" class="btn-primary" style="padding:0.4rem 0.75rem;">
                                <i class="fa-solid fa-camera"></i> Tandai Selesai
                            </button>
                        @else
                            <button type="button" wire:click="markStatus({{ $task->id }}, 'pending')" class="btn-ghost" style="padding:0.4rem 0.75rem;">
                                <i class="fa-solid fa-rotate-left"></i> Buka lagi
                            </button>
                        @endif
                        @if ($task->status !== 'done')
                            <button type="button" wire:click="deleteTask({{ $task->id }})"
                                    wire:confirm="Hapus tugas ini? Tindakan tidak bisa dibatalkan."
                                    class="btn-ghost" style="padding:0.4rem 0.75rem; color:#dc2626;">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </button>
                        @endif
                    </div>

                    @include('livewire.partials.comment-thread', ['type' => 'task', 'model' => $task])
                </article>
            @empty
                <div class="surface-card" style="padding:2.75rem 1.15rem; text-align:center;">
                    <i class="fa-regular fa-folder-open" style="font-size:1.6rem; color:var(--text-faint);"></i>
                    <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                        Belum ada tugas. Catat tugas yang disampaikan pembimbing secara langsung di sini.
                    </p>
                    <button type="button" wire:click="$dispatch('open-task-modal')" class="btn-primary" style="margin-top:1rem;">
                        <i class="fa-solid fa-plus"></i>
                        <span>Catat Tugas</span>
                    </button>
                </div>
            @endforelse
        </div>

        @if ($tasks->hasPages())
            <div class="flex items-center justify-between" style="margin-top:1.25rem;">
                <button wire:click="previousPage" class="btn-ghost" @disabled($tasks->onFirstPage())>
                    <i class="fa-solid fa-chevron-left"></i> Sebelumnya
                </button>
                <span style="font-size:0.8rem; color:var(--text-muted);">
                    Halaman {{ $tasks->currentPage() }} dari {{ $tasks->lastPage() }}
                </span>
                <button wire:click="nextPage" class="btn-ghost" @disabled(! $tasks->hasMorePages())>
                    Berikutnya <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        @endif
    @endif

    {{-- Modal catat tugas --}}
    <livewire:tasks.create />

    {{-- ===== Modal: unggah foto bukti sebelum tugas ditandai selesai ===== --}}
    <div
        x-data
        x-show="$wire.completingTaskId !== null"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$wire.completingTaskId !== null && $wire.closeComplete()"
        x-effect="document.body.style.overflow = $wire.completingTaskId !== null ? 'hidden' : ''"
        style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
    >
        <div
            @click.outside="$wire.closeComplete()"
            x-show="$wire.completingTaskId !== null"
            x-transition
            class="surface-card"
            style="width:100%; max-width:28rem; margin:auto; padding:0;"
        >
            <div class="flex items-center justify-between"
                 style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft);">
                <div>
                    <h2 style="font-size:1.05rem; font-weight:700;">Tandai Tugas Selesai</h2>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">
                        Unggah foto bukti hasil pengerjaan
                    </p>
                </div>
                <button type="button" wire:click="closeComplete" class="theme-toggle" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div style="padding:1.35rem;">
                <form wire:submit="confirmComplete">
                    <div style="margin-bottom:1.25rem;">
                        <span class="form-label">
                            Foto Bukti <span style="color:#dc2626; font-weight:600;">*</span>
                        </span>
                        <div x-data class="flex" style="gap:0.5rem; flex-wrap:wrap;">
                            <label class="btn-ghost" style="padding:0.5rem 0.85rem; cursor:pointer;">
                                <i class="fa-regular fa-image"></i> Pilih Foto
                                <input type="file" wire:model="completionPhoto" accept="image/*" style="display:none;">
                            </label>
                            <label class="btn-ghost" style="padding:0.5rem 0.85rem; cursor:pointer;">
                                <i class="fa-solid fa-camera"></i> Kamera
                                <input type="file" wire:model="completionPhoto" accept="image/*" capture="environment" style="display:none;">
                            </label>
                        </div>
                        <div wire:loading wire:target="completionPhoto" class="text-sm" style="color:var(--text-muted); margin-top:0.35rem;">
                            <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...
                        </div>
                        @if ($completionPhoto)
                            <img src="{{ $completionPhoto->temporaryUrl() }}" alt="Pratinjau foto"
                                 style="width:100px; height:100px; object-fit:cover; border-radius:10px; margin-top:0.6rem; border:1px solid var(--border);">
                        @endif
                        @error('completionPhoto')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label class="flex items-center text-sm"
                               style="gap:0.5rem; color:var(--text-body); cursor:pointer;">
                            <input type="checkbox" wire:model="addToJournal"
                                   style="width:1rem; height:1rem; border-radius:4px; accent-color:var(--brand); flex-shrink:0;">
                            Catat juga sebagai kegiatan di Jurnal Harian hari ini
                        </label>
                        <p class="text-sm" style="color:var(--text-faint); margin-top:0.3rem; margin-left:1.5rem;">
                            Judul & keterangan tugas plus foto bukti ini akan otomatis ditambahkan ke jurnal.
                        </p>
                    </div>

                    <div class="flex items-center" style="gap:0.65rem;">
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="confirmComplete">
                            <span wire:loading.remove wire:target="confirmComplete">
                                <i class="fa-solid fa-check" style="margin-right:0.4rem;"></i>Tandai Selesai
                            </span>
                            <span wire:loading wire:target="confirmComplete">
                                <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.4rem;"></i>Menyimpan...
                            </span>
                        </button>
                        <button type="button" wire:click="closeComplete" class="btn-ghost">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
