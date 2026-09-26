<div>
    <div class="flex items-center justify-between" style="gap:1rem; flex-wrap:wrap; margin-bottom:1.4rem;">
        <div>
            <h1 class="portal-title">Tugas Intern</h1>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
                Beri tugas langsung lewat web, atau pantau tugas yang dicatat sendiri oleh peserta magang
            </p>
        </div>
        <div class="flex items-center" style="gap:0.6rem; flex-wrap:wrap;">
            <button type="button"
                    x-data="{ state: 'off' }"
                    x-init="window.pushNotificationsActive?.().then(on => { if (on) state = 'on' })"
                    x-show="state !== 'on'"
                    x-on:click="enablePushNotifications().then(ok => { state = ok ? 'on' : 'off' })"
                    class="btn-ghost">
                <i class="fa-solid fa-bell"></i>
                <span>Aktifkan Notifikasi</span>
            </button>
            <button type="button" wire:click="openForm" class="btn-primary" style="flex-shrink:0;">
                <i class="fa-solid fa-plus"></i>
                <span>Beri Tugas</span>
            </button>
        </div>
    </div>

    <div x-data="{ show: false, count: 1 }" x-cloak
         x-on:task-assigned.window="count = $event.detail.count ?? 1; show = true; setTimeout(() => show = false, 4000)"
         x-show="show" x-transition
         class="surface-card flex items-center"
         style="gap:0.7rem; padding:0.8rem 1rem; margin-bottom:1rem; border-color:#a7f3d0;">
        <i class="fa-solid fa-circle-check" style="color:var(--brand-success);"></i>
        <span class="text-sm" style="color:var(--text-body);" x-text="count > 1 ? `Tugas berhasil diberikan ke ${count} peserta.` : 'Tugas berhasil diberikan.'">Tugas berhasil diberikan.</span>
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
                <option value="rejected">Ditunda intern</option>
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
                        <x-intern-avatar :intern="$task->intern" />
                        <span style="font-weight:700; color:var(--text-heading);">{{ $task->intern->nama ?? 'Peserta dihapus' }}</span>
                        @if ($task->intern?->unit)
                            <span class="badge badge-neutral"><i class="fa-solid fa-people-group"></i> {{ $task->intern->unit->name }}</span>
                        @endif
                        @if ($task->status === 'done')
                            <span class="badge" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-circle-check"></i> Selesai</span>
                        @elseif ($task->status === 'in_progress')
                            <span class="badge" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-spinner"></i> Dikerjakan</span>
                        @elseif ($task->status === 'rejected')
                            <span class="badge" style="background:#ffedd5; color:#c2410c;"><i class="fa-solid fa-circle-pause"></i> Ditunda intern</span>
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
                            <i class="fa-regular fa-calendar"></i> Tenggat {{ $task->due_date->translatedFormat('d F Y') }}
                            <i class="fa-regular fa-clock" style="margin-left:0.35rem;"></i> {{ $task->due_date->format('H:i') }}
                        </span>
                    @endif
                </div>

                <p style="margin-top:0.55rem; font-weight:600; color:var(--text-body);">{{ $task->title }}</p>
                @if ($task->description)
                    <p class="text-sm" style="margin-top:0.3rem; color:var(--text-body); white-space:pre-line;">{{ $task->description }}</p>
                @endif

                @if ($task->status === 'rejected' && $task->rejection_reason)
                    <p class="text-sm" style="margin-top:0.5rem; padding:0.6rem 0.75rem; background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; color:#c2410c;">
                        <i class="fa-solid fa-comment-dots"></i> Alasan intern menunda: {{ $task->rejection_reason }}
                    </p>
                @endif

                @if ($task->assignedBy)
                    <p class="text-sm" style="margin-top:0.5rem; color:var(--text-muted);">
                        <i class="fa-solid fa-user"></i>
                        {{ $task->source === 'web' ? 'Diberikan oleh' : 'Disebut sebagai pemberi tugas' }}: {{ $task->assignedBy->name }}
                        <span style="color:var(--text-faint);">&middot; {{ $task->created_at->translatedFormat('d F Y, H:i') }}</span>
                    </p>
                @endif

                @if ($task->status === 'done' && $task->completionPhotos->isNotEmpty())
                    <div style="margin-top:0.75rem;">
                        <div class="flex" style="gap:0.5rem; flex-wrap:wrap;">
                            @foreach ($task->completionPhotos as $photo)
                                <button type="button" onclick="openLightbox(@js(url('storage/' . $photo->path)), 'Bukti selesai')"
                                        style="display:inline-block; padding:0; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:none; cursor:zoom-in;">
                                    <img src="{{ url('storage/' . $photo->path) }}" alt="Bukti selesai"
                                         style="width:84px; height:84px; object-fit:cover; display:block;">
                                </button>
                            @endforeach
                        </div>
                        <p class="text-sm" style="margin-top:0.3rem; color:var(--text-faint);">
                            <i class="fa-regular fa-image"></i> Foto bukti pengerjaan dari intern
                        </p>
                    </div>
                @endif

                {{-- Bisa dikelola kalau: intern-nya memang mentee sendiri, ATAU tugas ini
                     memang aku sendiri yang berikan (lintas pembimbing tetap boleh diurus
                     oleh pembuatnya — lihat Tasks::manageableTasksQuery()). --}}
                @php($taskManageable = in_array($task->intern_id, $manageableInternIds, true) || $task->assigned_by === auth()->id())

                @if ($taskManageable)
                    <div class="flex items-center" style="gap:0.5rem; margin-top:0.9rem; padding-top:0.7rem; border-top:1px solid var(--border-soft); flex-wrap:wrap;">
                        @if ($task->status !== 'done' && $task->status !== 'rejected')
                            <button type="button" wire:click="markDone({{ $task->id }})" class="btn-ghost" style="padding:0.4rem 0.75rem;">
                                <i class="fa-solid fa-check"></i> Tandai Selesai
                            </button>
                        @else
                            <button type="button" wire:click="reopen({{ $task->id }})" class="btn-ghost" style="padding:0.4rem 0.75rem;">
                                <i class="fa-solid fa-rotate-left"></i> Buka lagi
                            </button>
                        @endif
                        <button type="button" wire:click="openEditTask({{ $task->id }})" class="btn-ghost" style="padding:0.4rem 0.75rem;">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                        <x-confirm-delete title="Hapus tugas ini?" confirm-wire-click="delete({{ $task->id }})">
                            <button type="button" @click="confirmOpen = true"
                                    class="btn-ghost" style="padding:0.4rem 0.75rem; color:#dc2626;">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </button>
                        </x-confirm-delete>
                    </div>
                @endif

                {{-- canComment default true — komentar boleh untuk semua intern yang terlihat
                     (lihat Tasks::resolveCommentable()), beda dari aksi kelola di atas. --}}
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
                    {{-- Pilih BEBERAPA peserta sekaligus kalau tugasnya sama (tiap peserta tetap dapat
                         tugasnya sendiri, lihat Tasks::assignTask()). Dua kotak terpisah — bimbingan/
                         mentee sendiri vs peserta lain (lintas pembimbing) — masing-masing dengan select
                         native sendiri: tiap kali satu nama dipilih, nama itu masuk ke daftar di kotak
                         tersebut dan select kembali kosong, jadi bisa terus menambah. Nama yang sudah
                         dipilih tidak muncul lagi di select. --}}
                    @php($myInterns = $allInterns->whereIn('id', $manageableInternIds))
                    @php($otherInterns = $allInterns->diff($myInterns))
                    @php($selectedIds = array_map('intval', $formInternIds))
                    <div style="margin-bottom:1.1rem;">
                        <div class="flex items-center justify-between" style="gap:0.5rem; flex-wrap:wrap;">
                            <span class="form-label" style="margin-bottom:0;">
                                Peserta
                                <span style="font-weight:400; color:var(--text-muted);">&middot; {{ count($selectedIds) }} dipilih</span>
                            </span>
                            @if ($selectedIds !== [])
                                <button type="button" class="text-sm" style="color:var(--text-muted); text-decoration:underline;"
                                        wire:click="$set('formInternIds', [])">
                                    Hapus pilihan
                                </button>
                            @endif
                        </div>

                        @foreach ([
                            ['key' => 'mine', 'label' => 'Peserta yang Kamu Bimbing/Mentori', 'icon' => 'fa-user-check', 'interns' => $myInterns],
                            ['key' => 'other', 'label' => 'Peserta Lain (Lintas Pembimbing)', 'icon' => 'fa-users', 'interns' => $otherInterns],
                        ] as $group)
                            @if ($group['interns']->isNotEmpty())
                                @php($available = $group['interns']->whereNotIn('id', $selectedIds))
                                @php($picked = $group['interns']->whereIn('id', $selectedIds))
                                <div wire:key="pick-group-{{ $group['key'] }}"
                                     style="margin-top:0.6rem; border:1px solid var(--border); border-radius:12px; padding:0.75rem; {{ $group['key'] === 'other' ? 'background:var(--surface-alt);' : '' }}">
                                    <div class="flex items-center justify-between" style="gap:0.5rem; margin-bottom:0.5rem;">
                                        <label for="a-intern-{{ $group['key'] }}"
                                               style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.04em; font-weight:700; color:var(--text-muted);">
                                            <i class="fa-solid {{ $group['icon'] }}" style="margin-right:0.3rem;"></i>{{ $group['label'] }}
                                            @if ($picked->isNotEmpty())
                                                <span style="font-weight:400;">({{ $picked->count() }} dipilih)</span>
                                            @endif
                                        </label>
                                        @if ($group['key'] === 'mine' && $available->isNotEmpty())
                                            <button type="button" class="text-sm" style="color:var(--brand); text-decoration:underline; flex-shrink:0;"
                                                    wire:click="selectAllMyInterns">
                                                Pilih semua
                                            </button>
                                        @endif
                                    </div>

                                    @if ($available->isNotEmpty())
                                        <select id="a-intern-{{ $group['key'] }}" class="form-input"
                                                x-data
                                                x-on:change="if ($event.target.value) { $wire.addFormIntern(Number($event.target.value)); } $event.target.value = ''">
                                            <option value="">{{ $picked->isEmpty() ? 'Pilih peserta...' : 'Tambah peserta lain...' }}</option>
                                            @foreach ($available as $i)
                                                <option value="{{ $i->id }}">{{ $i->nama }}{{ $i->unit ? ' — ' . $i->unit->name : '' }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <p class="text-sm" style="color:var(--text-faint);">Semua peserta di kelompok ini sudah dipilih.</p>
                                    @endif

                                    @if ($picked->isNotEmpty())
                                        <div class="flex" style="flex-wrap:wrap; gap:0.45rem; margin-top:0.6rem;">
                                            @foreach ($picked as $i)
                                                <button type="button" wire:key="pick-intern-{{ $i->id }}"
                                                        wire:click="toggleFormIntern({{ $i->id }})"
                                                        title="Batalkan pilihan"
                                                        class="text-sm"
                                                        style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.4rem 0.75rem; border-radius:9999px; cursor:pointer; background:var(--brand); color:#fff; border:1px solid var(--brand); font-weight:600;">
                                                    <span>{{ $i->nama }}{{ $i->unit ? ' — ' . $i->unit->name : '' }}</span>
                                                    <i class="fa-solid fa-xmark" style="font-size:0.75rem; opacity:0.85;"></i>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach

                        @error('formInternIds')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                        @error('formInternIds.*')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Kotak info pembimbing/mentor ASLI untuk peserta terpilih yang bukan
                         bimbingan sendiri — supaya kalau tugas diberikan lintas pembimbing,
                         tetap jelas siapa yang sebenarnya membimbing/mentori mereka. --}}
                    @if ($this->selectedOtherInterns !== [])
                        <div class="callout-warning" style="padding:0.75rem 0.9rem; margin-bottom:1.1rem;">
                            <p class="text-sm callout-warning-title" style="font-weight:600; margin-bottom:0.25rem;">
                                <i class="fa-solid fa-circle-info"></i> Peserta berikut bukan yang kamu bimbing/mentori
                            </p>
                            @foreach ($this->selectedOtherInterns as $other)
                                <p class="text-sm" style="color:var(--text-muted);">
                                    <strong style="color:var(--text-heading);">{{ $other['nama'] }}</strong> &mdash;
                                    Pembimbing: {{ $other['pembimbing'] ?? '—' }} &middot; Mentor: {{ $other['mentor'] ?? '—' }}
                                </p>
                            @endforeach
                        </div>
                    @endif

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
                        <label class="form-label">Tenggat <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                        <div class="flex items-start" style="gap:0.6rem;">
                            <div style="flex:1;">
                                <input id="a-due-date" type="date" wire:model="dueDate" class="form-input" aria-label="Tanggal tenggat">
                                @error('dueDate')
                                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div style="flex:1;">
                                <input id="a-due-time" type="time" wire:model="dueTime" class="form-input" aria-label="Jam tenggat">
                                @error('dueTime')
                                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <p class="text-sm" style="color:var(--text-faint); margin-top:0.35rem;">Isi tanggal dan jam terpisah — kalau jam dikosongkan, dianggap 00:00.</p>
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

    {{-- ===== Modal: edit tugas ===== --}}
    <div
        x-data
        x-show="$wire.editingTaskId !== null"
        x-cloak
        x-transition.opacity
        @keydown.escape.window="$wire.editingTaskId !== null && $wire.closeEditTask()"
        x-effect="document.body.style.overflow = $wire.editingTaskId !== null ? 'hidden' : ''"
        style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
    >
        <div
            @click.outside="$wire.closeEditTask()"
            x-show="$wire.editingTaskId !== null"
            x-transition
            class="surface-card"
            style="width:100%; max-width:32rem; margin:auto; padding:0;"
        >
            <div class="flex items-center justify-between"
                 style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft);">
                <div>
                    <h2 style="font-size:1.05rem; font-weight:700;">Edit Tugas</h2>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">Sesuaikan tugas ini — mis. kalau intern menunda tugas ini, ubah tenggat atau detailnya</p>
                </div>
                <button type="button" wire:click="closeEditTask" class="theme-toggle" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div style="padding:1.35rem;">
                <form wire:submit="confirmEditTask">
                    <div style="margin-bottom:1.1rem;">
                        <label for="e-title" class="form-label">Judul Tugas</label>
                        <input id="e-title" type="text" wire:model="editTitle" class="form-input">
                        @error('editTitle')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom:1.1rem;">
                        <label for="e-description" class="form-label">Keterangan <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                        <textarea id="e-description" wire:model="editDescription" rows="4" class="form-input"></textarea>
                        @error('editDescription')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label class="form-label">Tenggat <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
                        <div class="flex items-start" style="gap:0.6rem;">
                            <div style="flex:1;">
                                <input id="e-due-date" type="date" wire:model="editDueDate" class="form-input" aria-label="Tanggal tenggat">
                                @error('editDueDate')
                                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div style="flex:1;">
                                <input id="e-due-time" type="time" wire:model="editDueTime" class="form-input" aria-label="Jam tenggat">
                                @error('editDueTime')
                                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <p class="text-sm" style="color:var(--text-faint); margin-top:0.35rem;">Isi tanggal dan jam terpisah — kalau jam dikosongkan, dianggap 00:00.</p>
                    </div>

                    <div class="flex items-center" style="gap:0.65rem;">
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="confirmEditTask">
                            <span wire:loading.remove wire:target="confirmEditTask">
                                <i class="fa-solid fa-floppy-disk" style="margin-right:0.4rem;"></i>Simpan Perubahan
                            </span>
                            <span wire:loading wire:target="confirmEditTask">
                                <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.4rem;"></i>Menyimpan...
                            </span>
                        </button>
                        <button type="button" wire:click="closeEditTask" class="btn-ghost">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
