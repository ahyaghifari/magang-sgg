<div>
    <div class="flex items-center justify-between" style="gap:1rem; margin-bottom:1.4rem;">
        <div>
            <h1 class="portal-title">Tugas Intern</h1>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
                Beri tugas langsung lewat web, atau pantau tugas yang dicatat sendiri oleh peserta magang
            </p>
        </div>
        <button type="button" wire:click="openForm" class="btn-primary" style="flex-shrink:0;">
            <i class="fa-solid fa-plus"></i>
            <span>Beri Tugas</span>
        </button>
    </div>

    <div x-data="{ show: false }" x-cloak
         x-on:task-assigned.window="show = true; setTimeout(() => show = false, 4000)"
         x-show="show" x-transition
         class="surface-card flex items-center"
         style="gap:0.7rem; padding:0.8rem 1rem; margin-bottom:1rem; border-color:#a7f3d0;">
        <i class="fa-solid fa-circle-check" style="color:var(--brand-success);"></i>
        <span class="text-sm" style="color:var(--text-body);">Tugas berhasil diberikan.</span>
    </div>

    {{-- ===== Ringkasan ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem; margin-bottom:1.1rem;">
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Total Tugas</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $totalTasks }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Belum Selesai</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $pendingTasks }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Selesai</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $doneTasks }}</p>
        </div>
    </div>

    {{-- ===== Filter ===== --}}
    <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:1rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:0.75rem;">
        <div>
            <label for="f-intern" class="form-label">Peserta</label>
            <select id="f-intern" wire:model.live="internId" class="form-input">
                <option value="">Semua peserta</option>
                @foreach ($interns as $i)
                    <option value="{{ $i->id }}">{{ $i->nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-status" class="form-label">Status</label>
            <select id="f-status" wire:model.live="status" class="form-input">
                <option value="">Semua status</option>
                <option value="pending">Belum dikerjakan</option>
                <option value="in_progress">Dikerjakan</option>
                <option value="done">Selesai</option>
            </select>
        </div>
        <div>
            <label for="f-from" class="form-label">Dari tanggal</label>
            <input id="f-from" type="date" wire:model.live="dateFrom" class="form-input">
        </div>
        <div>
            <label for="f-to" class="form-label">Sampai tanggal</label>
            <input id="f-to" type="date" wire:model.live="dateTo" class="form-input">
        </div>
        @if ($dateFrom !== '' || $dateTo !== '')
            <div style="display:flex; align-items:flex-end;">
                <button type="button" wire:click="resetDateFilter" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                    <i class="fa-solid fa-xmark"></i> Reset tanggal
                </button>
            </div>
        @endif
    </div>

    {{-- ===== Daftar tugas ===== --}}
    <div class="flex" style="flex-direction:column; gap:0.75rem;">
        @forelse ($tasks as $task)
            <article class="surface-card" style="padding:1.1rem 1.15rem;">
                <div class="flex items-center justify-between" style="gap:0.75rem; flex-wrap:wrap;">
                    <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap;">
                        <span style="font-weight:700; color:var(--text-heading);">{{ $task->intern->nama ?? 'Peserta dihapus' }}</span>
                        @if ($task->intern?->unit)
                            <span class="badge badge-neutral"><i class="fa-solid fa-people-group"></i> {{ $task->intern->unit->name }}</span>
                        @endif
                        @if ($task->status === 'done')
                            <span class="badge" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-circle-check"></i> Selesai</span>
                        @elseif ($task->status === 'in_progress')
                            <span class="badge" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-spinner"></i> Dikerjakan</span>
                        @else
                            <span class="badge badge-neutral"><i class="fa-regular fa-circle"></i> Belum dikerjakan</span>
                        @endif
                        <span class="badge badge-neutral">
                            @if ($task->source === 'web')
                                <i class="fa-solid fa-globe"></i> Dari web
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

                <p style="margin-top:0.55rem; font-weight:600; color:var(--text-body);">{{ $task->title }}</p>
                @if ($task->description)
                    <p class="text-sm" style="margin-top:0.3rem; color:var(--text-body); white-space:pre-line;">{{ $task->description }}</p>
                @endif

                @if ($task->assignedBy)
                    <p class="text-sm" style="margin-top:0.5rem; color:var(--text-muted);">
                        <i class="fa-solid fa-user"></i>
                        {{ $task->source === 'web' ? 'Diberikan oleh' : 'Disebut sebagai pemberi tugas' }}: {{ $task->assignedBy->name }}
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
                            <i class="fa-regular fa-image"></i> Foto bukti pengerjaan dari intern
                        </p>
                    </div>
                @endif

                <div class="flex items-center" style="gap:0.5rem; margin-top:0.9rem; padding-top:0.7rem; border-top:1px solid var(--border-soft); flex-wrap:wrap;">
                    @if ($task->status !== 'done')
                        <button type="button" wire:click="markDone({{ $task->id }})" class="btn-ghost" style="padding:0.4rem 0.75rem;">
                            <i class="fa-solid fa-check"></i> Tandai Selesai
                        </button>
                    @else
                        <button type="button" wire:click="reopen({{ $task->id }})" class="btn-ghost" style="padding:0.4rem 0.75rem;">
                            <i class="fa-solid fa-rotate-left"></i> Buka lagi
                        </button>
                    @endif
                    <button type="button" wire:click="delete({{ $task->id }})"
                            wire:confirm="Hapus tugas ini?"
                            class="btn-ghost" style="padding:0.4rem 0.75rem; color:#dc2626;">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </button>
                </div>

                @include('livewire.partials.comment-thread', ['type' => 'task', 'model' => $task])
            </article>
        @empty
            <div class="surface-card" style="padding:2.75rem 1.15rem; text-align:center;">
                <i class="fa-regular fa-folder-open" style="font-size:1.6rem; color:var(--text-faint);"></i>
                <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                    Belum ada tugas yang cocok dengan filter.
                </p>
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

    {{-- ===== Modal: beri tugas ===== --}}
    <div
        x-data
        x-show="$wire.showForm"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$wire.showForm && $wire.closeForm()"
        x-effect="document.body.style.overflow = $wire.showForm ? 'hidden' : ''"
        style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
    >
        <div
            @click.outside="$wire.closeForm()"
            x-show="$wire.showForm"
            x-transition
            class="surface-card"
            style="width:100%; max-width:32rem; margin:auto; padding:0;"
        >
            <div class="flex items-center justify-between"
                 style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft);">
                <div>
                    <h2 style="font-size:1.05rem; font-weight:700;">Beri Tugas</h2>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">Tugas ini akan langsung tampil di halaman Tugas peserta</p>
                </div>
                <button type="button" wire:click="closeForm" class="theme-toggle" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div style="padding:1.35rem;">
                <form wire:submit="assignTask">
                    <div style="margin-bottom:1.1rem;">
                        <label for="a-intern" class="form-label">Peserta</label>
                        <select id="a-intern" wire:model="formInternId" class="form-input">
                            <option value="">Pilih peserta...</option>
                            @foreach ($interns as $i)
                                <option value="{{ $i->id }}">{{ $i->nama }}</option>
                            @endforeach
                        </select>
                        @error('formInternId')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom:1.1rem;">
                        <label for="a-title" class="form-label">Judul Tugas</label>
                        <input id="a-title" type="text" wire:model="title" class="form-input"
                               placeholder="Contoh: Buat laporan mingguan unit IT">
                        @error('title')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom:1.1rem;">
                        <label for="a-description" class="form-label">Keterangan <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                        <textarea id="a-description" wire:model="description" rows="4" class="form-input"
                                  placeholder="Detail tugas..."></textarea>
                        @error('description')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label for="a-due" class="form-label">Tenggat <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                        <input id="a-due" type="date" wire:model="dueDate" class="form-input" style="max-width:14rem;">
                        @error('dueDate')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center" style="gap:0.65rem;">
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="assignTask">
                            <span wire:loading.remove wire:target="assignTask">
                                <i class="fa-solid fa-paper-plane" style="margin-right:0.4rem;"></i>Beri Tugas
                            </span>
                            <span wire:loading wire:target="assignTask">
                                <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.4rem;"></i>Menyimpan...
                            </span>
                        </button>
                        <button type="button" wire:click="closeForm" class="btn-ghost">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
