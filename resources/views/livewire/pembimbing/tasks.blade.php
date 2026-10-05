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

    {{-- ===== Tombol cepat cakupan: sekali klik langsung tampil semua tugas bimbingan/mentee
         sendiri (intern yang langsung ditugaskan ke user ini sebagai pembimbing/mentor).
         Tampil untuk Pembimbing, Mentor, dan Admin yang punya bimbingan/mentee. ===== --}}
    @if ($canScope)
        <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem;" role="group" aria-label="Tampilkan tugas">
            @php
                // Teks tombol menyesuaikan peran: Pembimbing → "Bimbingan saya", Mentor (pendamping) → "Dampingan saya".
                $mineLabel = match (true) {
                    auth()->user()->isPembimbing() => 'Bimbingan saya',
                    auth()->user()->isMentor() => 'Dampingan saya',
                    default => 'Bimbingan/dampingan saya',
                };
                $scopeOptions = [
                    ['value' => '', 'label' => 'Semua intern', 'icon' => 'fa-users', 'count' => $scopeCounts['all']],
                    ['value' => 'mine', 'label' => $mineLabel, 'icon' => 'fa-user-check', 'count' => $scopeCounts['mine']],
                ];
            @endphp
            @foreach ($scopeOptions as $opt)
                @php
                    $active = $scope === $opt['value'];
                    $label = $opt['label'];
                    $icon = $opt['icon'];
                    $count = $opt['count'];
                @endphp
                <button type="button" wire:click="$set('scope', '{{ $opt['value'] }}')" aria-pressed="{{ $active ? 'true' : 'false' }}"
                        style="display:inline-flex; align-items:center; gap:0.45rem; padding:0.55rem 1rem; border-radius:9999px; font-size:0.875rem; font-weight:600; cursor:pointer; transition:all .15s;
                               {{ $active
                                   ? 'background:var(--brand); color:#fff; border:1px solid var(--brand); box-shadow:0 4px 12px rgba(4,44,108,.18);'
                                   : 'background:var(--surface); color:var(--text-body); border:1px solid var(--border);' }}">
                    <i class="fa-solid {{ $icon }}"></i>
                    {{ $label }}
                    <span style="font-size:0.75rem; font-weight:700; padding:0.05rem 0.45rem; border-radius:9999px; {{ $active ? 'background:rgba(255,255,255,.22);' : 'background:var(--surface-alt); color:var(--text-muted);' }}">{{ $count }}</span>
                </button>
            @endforeach
        </div>
    @endif

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
        {{-- Satu kotak = satu pengiriman tugas, bisa untuk beberapa intern sekaligus (lihat
             Tasks::paginateTaskGroups() & Task::groupKey()). Kalau isi tugasnya SAMA, judul/
             keterangan/tenggat tampil sekali di atas. Kalau BERBEDA (mis. pakai "Tugas berbeda
             untuk tiap peserta", atau satu peserta diedit sendiri), bagian yang berbeda tampil
             per baris peserta dan edit dilakukan per peserta. Tiap intern punya baris sendiri
             berisi status, aksi, foto bukti, dan diskusi (sisi intern tetap satu per satu). --}}
        @forelse ($taskGroups as $group)
            @php
                $groupTasks = $group['tasks'];
                $first = $groupTasks->first();
                $isMulti = $groupTasks->count() > 1;
                $myId = auth()->id();
                $canManage = fn ($t) => in_array($t->intern_id, $manageableInternIds, true) || $t->assigned_by === $myId;
                $manageableIds = $groupTasks->filter($canManage)->pluck('id')->values()->all();
                $doneCount = $groupTasks->where('status', 'done')->count();

                $sameTitle = $groupTasks->pluck('title')->unique()->count() <= 1;
                $sameDesc = $groupTasks->map(fn ($t) => trim((string) $t->description))->unique()->count() <= 1;
                $sameDue = $groupTasks->map(fn ($t) => $t->due_date?->format('Y-m-d H:i'))->unique()->count() <= 1;
                // "Edit untuk semua" hanya aman kalau isinya memang sama persis.
                $uniform = $sameTitle && $sameDesc && $sameDue;
            @endphp
            <article wire:key="task-group-{{ md5($group['key']) }}" class="surface-card" style="padding:1.1rem 1.15rem;">
                {{-- Kolom judul punya lebar dasar 16rem: di HP tenggat otomatis turun ke baris sendiri
                     (dulu tenggat tidak boleh menyusut, jadi judul & label terjepit jadi sempit). --}}
                <div class="flex items-start justify-between" style="gap:0.5rem 0.75rem; flex-wrap:wrap;">
                    <div style="min-width:0; flex:1 1 16rem;">
                        {{-- Isi berbeda per peserta → judul kotak "Tugas berbeda untuk tiap peserta";
                             judul tugas aslinya (kalau semua masih sama) tampil kecil di bawahnya. --}}
                        <p style="font-weight:700; font-size:1rem; color:var(--text-heading);">
                            {{ $uniform ? $first->title : 'Tugas berbeda untuk tiap peserta' }}
                        </p>
                        @if (! $uniform && $sameTitle)
                            <p class="text-sm" style="color:var(--text-muted); margin-top:0.1rem;">
                                <i class="fa-solid fa-thumbtack" style="font-size:0.75rem;"></i> {{ $first->title }}
                            </p>
                        @endif
                        <div class="flex items-center" style="gap:0.4rem; flex-wrap:wrap; margin-top:0.35rem;">
                            <span class="badge badge-neutral">
                                @if ($first->source === 'web')
                                    <i class="fa-solid fa-globe"></i> Dari web
                                @else
                                    <i class="fa-solid fa-comment"></i> Lisan
                                @endif
                            </span>
                            @if ($isMulti)
                                <span class="badge" style="background:#e0e7ff; color:#3730a3;">
                                    <i class="fa-solid fa-users"></i> {{ $groupTasks->count() }} peserta
                                </span>
                                <span class="badge" style="{{ $doneCount === $groupTasks->count() ? 'background:#dcfce7; color:#15803d;' : 'background:var(--surface-alt); color:var(--text-muted);' }}">
                                    <i class="fa-solid fa-circle-check"></i> {{ $doneCount }}/{{ $groupTasks->count() }} selesai
                                </span>
                                @unless ($uniform)
                                    <span class="badge" style="background:#fef3c7; color:#92400e;">
                                        <i class="fa-solid fa-user-pen"></i> Isi berbeda per peserta
                                    </span>
                                @endunless
                            @endif
                        </div>
                    </div>
                    @if ($sameDue && $first->due_date)
                        <span class="text-sm" style="color:var(--text-muted); flex:0 1 auto;">
                            <i class="fa-regular fa-calendar"></i> Tenggat {{ $first->due_date->translatedFormat('d F Y') }}
                            <i class="fa-regular fa-clock" style="margin-left:0.35rem;"></i> {{ $first->due_date->format('H:i') }}
                        </span>
                    @endif
                </div>

                @if ($sameDesc && $first->description)
                    <p class="text-sm" style="margin-top:0.55rem; color:var(--text-body); white-space:pre-line;">{{ $first->description }}</p>
                @endif

                @if ($first->assignedBy)
                    <p class="text-sm" style="margin-top:0.5rem; color:var(--text-muted);">
                        <i class="fa-solid fa-user"></i>
                        {{ $first->source === 'web' ? 'Diberikan oleh' : 'Disebut sebagai pemberi tugas' }}: {{ $first->assignedBy->name }}
                        <span style="color:var(--text-faint);">&middot; {{ $first->created_at->translatedFormat('d F Y, H:i') }}</span>
                    </p>
                @endif

                {{-- Aksi untuk seluruh kotak: "Edit untuk semua" hanya kalau isi tugasnya sama
                     (kalau berbeda, edit lewat tombol di tiap baris peserta); hapus menghapus semuanya. --}}
                @if ($manageableIds !== [])
                    <div class="flex items-center" style="gap:0.5rem; margin-top:0.8rem; flex-wrap:wrap;">
                        @if ($uniform)
                            <button type="button" wire:click="openEditTask({{ $manageableIds[0] }}, {{ Js::from($manageableIds) }})" class="btn-ghost" style="padding:0.4rem 0.75rem;">
                                <i class="fa-solid fa-pen"></i> Edit{{ $isMulti ? ' untuk semua' : '' }}
                            </button>
                        @endif
                        <x-confirm-delete :title="$isMulti ? 'Hapus tugas ini untuk semua peserta?' : 'Hapus tugas ini?'"
                                          confirm-wire-click="deleteGroup({{ Js::from($manageableIds) }})">
                            <button type="button" @click="confirmOpen = true"
                                    class="btn-ghost" style="padding:0.4rem 0.75rem; color:#dc2626;">
                                <i class="fa-solid fa-trash"></i> Hapus{{ $isMulti ? ' semua' : '' }}
                            </button>
                        </x-confirm-delete>
                    </div>
                @endif

                {{-- Daftar peserta di kotak ini --}}
                <div style="margin-top:0.9rem; border:1px solid var(--border-soft); border-radius:12px; overflow:hidden;">
                    @foreach ($groupTasks as $task)
                        @php($taskManageable = $canManage($task))
                        <div wire:key="task-row-{{ $task->id }}"
                             x-data="{ diskusi: false }"
                             style="padding:0.75rem 0.85rem; {{ $loop->first ? '' : 'border-top:1px solid var(--border-soft);' }}">
                            <div class="flex items-center justify-between" style="gap:0.6rem; flex-wrap:wrap;">
                                <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap; min-width:0;">
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
                                </div>
                                <div class="flex items-center" style="gap:0.4rem; flex-wrap:wrap;">
                                    @if ($taskManageable)
                                        @if ($task->status !== 'done' && $task->status !== 'rejected')
                                            <button type="button" wire:click="markDone({{ $task->id }})" class="btn-ghost" style="padding:0.3rem 0.6rem; font-size:0.8rem;">
                                                <i class="fa-solid fa-check"></i> Tandai Selesai
                                            </button>
                                        @else
                                            <button type="button" wire:click="reopen({{ $task->id }})" class="btn-ghost" style="padding:0.3rem 0.6rem; font-size:0.8rem;">
                                                <i class="fa-solid fa-rotate-left"></i> Buka lagi
                                            </button>
                                        @endif
                                        @unless ($uniform)
                                            <button type="button" wire:click="openEditTask({{ $task->id }})" title="Edit tugas peserta ini"
                                                    class="btn-ghost" style="padding:0.3rem 0.6rem; font-size:0.8rem;">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </button>
                                        @endunless
                                        @if ($isMulti)
                                            <x-confirm-delete title="Hapus tugas ini untuk {{ $task->intern->nama ?? 'peserta ini' }} saja?" confirm-wire-click="delete({{ $task->id }})">
                                                <button type="button" @click="confirmOpen = true" title="Hapus untuk peserta ini saja"
                                                        class="btn-ghost" style="padding:0.3rem 0.55rem; font-size:0.8rem; color:#dc2626;">
                                                    <i class="fa-solid fa-user-minus"></i>
                                                </button>
                                            </x-confirm-delete>
                                        @endif
                                    @endif
                                    @if ($isMulti)
                                        <button type="button" @click="diskusi = ! diskusi" class="btn-ghost" style="padding:0.3rem 0.6rem; font-size:0.8rem;">
                                            <i class="fa-regular fa-comments"></i> Diskusi{{ $task->comments->isNotEmpty() ? ' (' . $task->comments->count() . ')' : '' }}
                                            <i class="fa-solid" :class="diskusi ? 'fa-chevron-up' : 'fa-chevron-down'" style="font-size:0.65rem;"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Isi tugas milik peserta ini — hanya bagian yang berbeda antar-peserta. --}}
                            @if (! $uniform && ((! $sameTitle) || (! $sameDue && $task->due_date) || (! $sameDesc && $task->description)))
                                <div style="margin-top:0.5rem; padding:0.55rem 0.7rem; background:var(--surface-alt); border-radius:10px;">
                                    @unless ($sameTitle)
                                        <p style="font-weight:700; font-size:0.9rem; color:var(--text-heading);">{{ $task->title }}</p>
                                    @endunless
                                    @if (! $sameDue && $task->due_date)
                                        <p class="text-sm" style="color:var(--text-muted); {{ $sameTitle ? '' : 'margin-top:0.15rem;' }}">
                                            <i class="fa-regular fa-calendar"></i> Tenggat {{ $task->due_date->translatedFormat('d F Y') }}
                                            <i class="fa-regular fa-clock" style="margin-left:0.35rem;"></i> {{ $task->due_date->format('H:i') }}
                                        </p>
                                    @endif
                                    @if (! $sameDesc && $task->description)
                                        <p class="text-sm" style="color:var(--text-body); white-space:pre-line; {{ $sameTitle && ($sameDue || ! $task->due_date) ? '' : 'margin-top:0.3rem;' }}">{{ $task->description }}</p>
                                    @endif
                                </div>
                            @endif

                            @if ($task->status === 'rejected' && $task->rejection_reason)
                                <p class="text-sm" style="margin-top:0.5rem; padding:0.55rem 0.7rem; background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; color:#c2410c;">
                                    <i class="fa-solid fa-comment-dots"></i> Alasan intern menunda: {{ $task->rejection_reason }}
                                </p>
                            @endif

                            @if ($task->status === 'done' && $task->completionPhotos->isNotEmpty())
                                <div class="flex" style="gap:0.45rem; flex-wrap:wrap; margin-top:0.55rem;">
                                    @foreach ($task->completionPhotos as $photo)
                                        <button type="button" onclick="openLightbox(@js(url('storage/' . $photo->path)), @js('Bukti selesai — ' . ($task->intern->nama ?? '')))"
                                                style="display:inline-block; padding:0; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:none; cursor:zoom-in;">
                                            <img src="{{ url('storage/' . $photo->path) }}" alt="Bukti selesai"
                                                 style="width:72px; height:72px; object-fit:cover; display:block;">
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Diskusi per intern. Kotak berisi satu intern: langsung tampil; kotak
                                 berisi beberapa intern: dibuka lewat tombol "Diskusi" di barisnya.
                                 canComment default true (lihat Tasks::resolveCommentable()). --}}
                            @if ($isMulti)
                                <div x-show="diskusi" x-cloak x-transition>
                                    @include('livewire.partials.comment-thread', ['type' => 'task', 'model' => $task])
                                </div>
                            @else
                                @include('livewire.partials.comment-thread', ['type' => 'task', 'model' => $task])
                            @endif
                        </div>
                    @endforeach
                </div>
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

    @if ($taskGroups->hasPages())
        <div class="flex items-center justify-between" style="margin-top:1.25rem;">
            <button wire:click="previousPage" class="btn-ghost" @disabled($taskGroups->onFirstPage())>
                <i class="fa-solid fa-chevron-left"></i> Sebelumnya
            </button>
            <span style="font-size:0.8rem; color:var(--text-muted);">
                Halaman {{ $taskGroups->currentPage() }} dari {{ $taskGroups->lastPage() }}
            </span>
            <button wire:click="nextPage" class="btn-ghost" @disabled(! $taskGroups->hasMorePages())>
                Berikutnya <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    @endif

    {{-- ===== Modal: beri tugas =====
         Sengaja TIDAK menutup saat klik di luar kotak — supaya isian tidak hilang karena
         salah klik. Tutup lewat tombol ✕ / Batal / tombol Esc. --}}
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
                        <label for="a-title" class="form-label">Judul Tugas <span style="color:var(--text-faint); font-weight:400;">(opsional — kosong = diambil dari keterangan)</span></label>
                        <input id="a-title" type="text" wire:model="title" class="form-input"
                               placeholder="Contoh: Buat laporan mingguan unit IT">
                        @error('title')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ===== Opsional: tugas berbeda untuk tiap peserta =====
                         Hanya muncul kalau peserta terpilih >= 2; default tertutup. Buka/tutup cuma
                         state tampilan (Alpine) — isi kotak tetap tersimpan di $perInternNotes dan
                         tetap dipakai saat simpan walau bagiannya ditutup. Kotak ikut daftar chip
                         peserta terpilih; kotak kosong = peserta itu memakai Keterangan umum
                         (lihat Tasks::descriptionFor()). --}}
                    @php($notePeserta = $allInterns->whereIn('id', $selectedIds)->sortBy('nama')->values())
                    @if ($notePeserta->count() >= 2)
                        @php($filledNotes = collect($perInternNotes)->filter(fn ($n) => trim((string) $n) !== '')->count())
                        <div wire:key="per-intern-notes"
                             x-data="{ open: false }"
                             style="margin-bottom:1.1rem; border:1px solid var(--border); border-radius:12px; overflow:hidden;">
                            <button type="button" @click="open = ! open" :aria-expanded="open"
                                    class="flex items-center justify-between"
                                    style="width:100%; gap:0.6rem; padding:0.7rem 0.85rem; background:var(--surface-alt); border:0; cursor:pointer; text-align:left;">
                                <span style="min-width:0;">
                                    <span style="display:block; font-size:0.85rem; font-weight:700; color:var(--text-heading);">
                                        <i class="fa-solid fa-user-pen" style="color:var(--brand); margin-right:0.35rem;"></i>
                                        Tugas berbeda untuk tiap peserta
                                        <span style="font-weight:400; color:var(--text-faint);">(opsional)</span>
                                    </span>
                                    <span style="display:block; font-size:0.75rem; color:var(--text-muted); margin-top:0.1rem;">
                                        @if ($filledNotes > 0)
                                            {{ $filledNotes }} dari {{ $notePeserta->count() }} peserta punya tugas khusus
                                        @else
                                            Tambahkan tugas khusus untuk peserta tertentu
                                        @endif
                                    </span>
                                </span>
                                <i class="fa-solid" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" style="color:var(--text-muted); flex-shrink:0;"></i>
                            </button>

                            <div x-show="open" x-cloak x-transition style="padding:0.75rem 0.85rem; border-top:1px solid var(--border);">
                                <div class="flex items-center justify-between" style="gap:0.5rem; flex-wrap:wrap; margin-bottom:0.6rem;">
                                    <p style="font-size:0.75rem; color:var(--text-muted); flex:1; min-width:12rem;">
                                        Kotak kosong = peserta itu memakai Keterangan umum. Kalau diisi, keterangan
                                        umum tampil di atas lalu tugas khusus di bawahnya.
                                    </p>
                                    {{-- Konfirmasi dulu kalau ada kotak yang sudah diisi, karena isinya akan tertimpa. --}}
                                    <button type="button" class="text-sm"
                                            style="color:var(--brand); text-decoration:underline; flex-shrink:0;"
                                            @click="
                                                const filled = [...$root.querySelectorAll('textarea[data-note]')].some(t => t.value.trim() !== '');
                                                if (! filled || confirm('Isi kotak yang sudah diketik akan ditimpa dengan keterangan umum. Lanjutkan?')) { $wire.copyGeneralToAll() }
                                            ">
                                        Salin keterangan umum ke semua kotak
                                    </button>
                                </div>

                                <div class="flex" style="flex-direction:column; gap:0.6rem; max-height:20rem; overflow-y:auto; padding-right:0.15rem; overscroll-behavior:contain;">
                                    @foreach ($notePeserta as $p)
                                        @php($isOther = ! in_array($p->id, $manageableInternIds, true))
                                        <div wire:key="note-box-{{ $p->id }}"
                                             style="border:1px solid var(--border-soft); border-radius:10px; padding:0.6rem 0.7rem; background:var(--surface);">
                                            <div class="flex items-center" style="gap:0.5rem; margin-bottom:0.45rem; min-width:0;">
                                                <x-intern-avatar :intern="$p" size="1.7rem" />
                                                <label for="note-{{ $p->id }}" style="font-size:0.85rem; color:var(--text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                                    Tugas khusus <b style="color:var(--text-heading);">{{ $p->nama }}</b>
                                                </label>
                                                @if ($isOther)
                                                    <span class="badge" style="background:#fef3c7; color:#92400e; flex-shrink:0;">lintas</span>
                                                @endif
                                            </div>
                                            <textarea id="note-{{ $p->id }}" data-note
                                                      wire:model="perInternNotes.{{ $p->id }}" rows="2" class="form-input"
                                                      aria-label="Tugas khusus {{ $p->nama }}"
                                                      placeholder="Kosongkan kalau sama dengan keterangan umum"></textarea>
                                            @error('perInternNotes.' . $p->id)
                                                <p class="text-sm" style="color:#dc2626; margin-top:0.35rem;">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <div style="margin-bottom:1.1rem;">
                        {{-- Dengan >= 2 peserta, labelnya dipertegas "umum" supaya jelas bedanya dengan
                             kotak "Tugas khusus" per peserta di atas. --}}
                        <label for="a-description" class="form-label">
                            @if (count($selectedIds) >= 2)
                                Keterangan umum <span style="color:var(--text-faint); font-weight:400;">(untuk semua peserta, opsional)</span>
                            @else
                                Keterangan <span style="color:var(--text-faint); font-weight:400;">(opsional)</span>
                            @endif
                        </label>
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

    {{-- ===== Modal: edit tugas ===== (sama: tidak tertutup saat klik di luar kotak) --}}
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
                        <label for="e-title" class="form-label">Judul Tugas <span style="color:var(--text-faint); font-weight:400;">(opsional)</span></label>
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
