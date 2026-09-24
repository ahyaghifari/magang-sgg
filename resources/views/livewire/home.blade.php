<div>
    <h1 class="portal-title" style="margin-bottom:1.4rem;">Beranda</h1>

    @if ($journalSaved)
        <div class="surface-card flex items-center"
             style="gap:0.7rem; padding:0.8rem 1rem; margin-bottom:1rem; border-color:#a7f3d0;"
             x-data x-init="setTimeout(() => $wire.set('journalSaved', false), 4000)">
            <i class="fa-solid fa-circle-check" style="color:var(--brand-success);"></i>
            <span class="text-sm" style="color:var(--text-body);">Jurnal berhasil disimpan.</span>
        </div>
    @endif

    {{-- ===== Welcome card ===== --}}
    @php($isLight = $intern?->dashboard_color && $intern->isDashboardColorLight())
    @php($fg = $isLight ? '#0f172a' : '#fff')
    @php($overlaySoft = $isLight ? 'rgba(15,23,42,0.08)' : 'rgba(255,255,255,0.16)')
    @php($overlayBorder = $isLight ? 'rgba(15,23,42,0.22)' : 'rgba(255,255,255,0.5)')
    <section class="welcome-card {{ $isLight ? 'is-light-bg' : '' }}"
             style="padding: 1.5rem; {{ $intern?->dashboard_color ? 'background:' . $intern->dashboard_color . ';' : '' }}">
        <span class="w-deco-1"></span>
        <span class="w-deco-2"></span>
        <span class="w-deco-3"></span>

        <div style="position: relative; z-index: 1;">
            @if ($intern)
                <button type="button" wire:click="openProfileModal"
                        style="position:absolute; top:0; right:0; background:{{ $overlaySoft }}; border:0; color:{{ $fg }}; width:2.1rem; height:2.1rem; border-radius:9999px; cursor:pointer;"
                        aria-label="Ubah profil">
                    <i class="fa-solid fa-pen"></i>
                </button>
            @endif

            <div class="flex items-center" style="gap:0.85rem;">
                @if ($intern?->avatar_path)
                    <img src="{{ url('storage/' . $intern->avatar_path) }}" alt="Foto profil"
                         style="width:3.2rem; height:3.2rem; border-radius:9999px; object-fit:cover; border:2px solid {{ $overlayBorder }}; flex-shrink:0;">
                @elseif ($intern)
                    <div style="width:3.2rem; height:3.2rem; border-radius:9999px; background:{{ $overlaySoft }}; border:2px solid {{ $overlayBorder }}; color:{{ $fg }}; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.1rem; flex-shrink:0;">
                        {{ \Illuminate\Support\Str::of($intern->nama)->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('') }}
                    </div>
                @endif

                <div>
                    <p style="font-size:0.8125rem; opacity:0.8; color:{{ $fg }};">
                        <i class="fa-regular fa-calendar" style="margin-right:0.4rem;"></i>
                        {{ \Illuminate\Support\Carbon::now()->translatedFormat('l, d F Y') }}
                    </p>
                    <p style="margin-top:0.4rem; font-size:1.375rem; font-weight:700; color:{{ $fg }};">
                        Halo, {{ $intern?->nama ?? $user->name }} 👋
                    </p>
                </div>
            </div>
            @unless ($intern)
                <p style="margin-top:0.25rem; font-size:0.875rem; opacity:0.85; color:{{ $fg }};">
                    Selamat datang di portal Internship Syifa Global Group
                </p>
            @endunless

            @if ($intern)
                <div class="flex" style="flex-wrap:wrap; gap:0.6rem; margin-top:1.15rem;">
                    <button type="button" wire:click="$dispatch('open-journal-modal')" class="qa-btn">
                        <i class="fa-solid fa-pen-to-square"></i> Isi Jurnal Hari Ini
                    </button>
                    <a href="{{ route('journals.index') }}" wire:navigate class="qa-btn">
                        <i class="fa-solid fa-list-ul"></i> Semua Jurnal
                    </a>
                    @if ($intern->tanggal_selesai && $intern->tanggal_selesai->lte(\Illuminate\Support\Carbon::now()))
                        <a href="{{ route('interns.certificate.view', $intern) }}" target="_blank" class="qa-btn">
                            <i class="fa-solid fa-eye"></i> Lihat Sertifikat
                        </a>
                        <a href="{{ route('interns.certificate', $intern) }}" class="qa-btn">
                            <i class="fa-solid fa-award"></i> Download Sertifikat
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- ===== Tugas belum dikerjakan ===== --}}
    @if ($pendingTasksCount > 0)
        <a href="{{ route('tasks.index') }}" wire:navigate class="task-card" style="padding:1.15rem 1.25rem; margin-top:0.85rem;">
            <span class="t-deco-1"></span>
            <span class="t-deco-2"></span>

            <div class="flex items-center" style="gap:0.9rem; position:relative; z-index:1;">
                <span class="t-icon-badge">
                    <i class="fa-solid fa-clipboard-list"></i>
                </span>

                <div style="min-width:0; flex:1;">
                    <div class="flex items-center" style="gap:0.5rem;">
                        <p style="font-weight:700; font-size:0.95rem;">Tugas belum selesai</p>
                        <span class="t-count-badge">{{ $pendingTasksCount }}</span>
                    </div>
                    <p style="margin-top:0.2rem; font-size:0.8rem; opacity:0.9; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $pendingTasks->pluck('title')->join(', ') }}
                    </p>
                </div>

                <i class="fa-solid fa-chevron-right" style="opacity:0.8; flex-shrink:0;"></i>
            </div>
        </a>
    @endif

    {{-- ===== Presensi hari ini ===== --}}
    @if ($intern && filled($intern->nip))
        @php($libur = ! $schedule || $schedule->is_off_day)
        @php($fmtTime = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->format('H:i') : '—')
        @php($statusLabel = match ($todayAttendance?->status) {
            'present' => 'Hadir',
            'late' => 'Telat',
            'absent' => 'Alfa',
            default => null,
        })

        <section class="presensi-card" style="padding: 1.5rem; margin-top: 0.85rem;">
            <span class="w-deco-1"></span>
            <span class="w-deco-2"></span>
            <span class="w-deco-3"></span>

            <div style="position: relative; z-index: 1;">
                <p style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; opacity:0.85;">
                    <i class="fa-regular fa-clock" style="margin-right:0.4rem;"></i>Presensi Hari Ini
                </p>
                <div class="flex" style="gap:2.5rem; margin-top:1.1rem;">
                    <div>
                        <p style="font-size:0.75rem; opacity:0.8;">Jam Masuk</p>
                        <p style="margin-top:0.2rem; font-size:1.5rem; font-weight:700;">{{ $fmtTime($todayAttendance?->check_in_time) }}</p>
                    </div>
                    <div>
                        <p style="font-size:0.75rem; opacity:0.8;">Jam Keluar</p>
                        <p style="margin-top:0.2rem; font-size:1.5rem; font-weight:700;">{{ $fmtTime($todayAttendance?->check_out_time) }}</p>
                    </div>
                </div>

                <div style="margin-top:1rem;">
                    @if ($statusLabel)
                        <span class="p-badge">
                            <i class="fa-solid fa-circle-check"></i> {{ $statusLabel }}
                        </span>
                    @elseif ($libur)
                        <span class="p-badge"><i class="fa-solid fa-mug-hot"></i> Libur</span>
                    @else
                        <span class="p-badge"><i class="fa-regular fa-circle"></i> Belum presensi</span>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if (! $intern)
        {{-- ===== No intern state ===== --}}
        <div class="surface-card flex items-center"
             style="gap:0.85rem; padding:1rem 1.15rem; margin-top:1.25rem;">
            <i class="fa-solid fa-circle-info" style="color:#d97706; font-size:1.1rem;"></i>
            <p class="text-sm" style="color:var(--text-body);">
                Akun kamu belum terhubung dengan data <strong>Intern</strong>.
                Hubungi admin untuk membuatkan data magang terlebih dahulu.
            </p>
        </div>
    @else
        {{-- ===== Intern info ===== --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:0.85rem; margin-top:1.25rem;">
            <div class="surface-card flex items-center" style="padding:0.95rem 1.05rem; gap:0.8rem;">
                <span class="info-icon-badge navy"><i class="fa-solid fa-building"></i></span>
                <div style="min-width:0;">
                    <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Institusi</p>
                    <p class="text-sm" style="margin-top:0.2rem; font-weight:700; color:var(--text-heading); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $intern->institusi->name ?? '—' }}
                    </p>
                </div>
            </div>
            <div class="surface-card flex items-center" style="padding:0.95rem 1.05rem; gap:0.8rem;">
                <span class="info-icon-badge green"><i class="fa-solid fa-id-badge"></i></span>
                <div style="min-width:0;">
                    <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Nama Peserta</p>
                    <p class="text-sm" style="margin-top:0.2rem; font-weight:700; color:var(--text-heading); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $intern->nama }}
                    </p>
                </div>
            </div>
        </div>

        {{-- ===== Stats ===== --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem; margin-top:0.85rem;">
            <div class="stat-card" style="padding:1.1rem 1.15rem;">
                <span class="stat-card-deco"></span>
                <span class="stat-card-icon navy"><i class="fa-solid fa-book"></i></span>
                <p style="margin-top:0.7rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Total Jurnal</p>
                <p style="margin-top:0.2rem; font-size:1.75rem; font-weight:800; color:var(--brand);">{{ $totalJournals }}</p>
            </div>
            <div class="stat-card" style="padding:1.1rem 1.15rem;">
                <span class="stat-card-deco"></span>
                <span class="stat-card-icon magenta"><i class="fa-regular fa-clock"></i></span>
                <p style="margin-top:0.7rem; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Jurnal Terakhir</p>
                <p class="text-sm" style="margin-top:0.3rem; font-weight:700; color:var(--text-heading);">
                    {{ $lastJournalDate ? \Illuminate\Support\Carbon::parse($lastJournalDate)->translatedFormat('d M Y') : 'Belum ada' }}
                </p>
            </div>
        </div>

        {{-- ===== Recent journals ===== --}}
        <div class="surface-card" style="margin-top:0.85rem; overflow:hidden;">
            <div class="flex items-center justify-between"
                 style="padding:0.9rem 1.15rem; border-bottom:1px solid var(--border-soft);">
                <h2 class="text-sm" style="font-weight:700; color:var(--text-heading);">Jurnal Terbaru</h2>
                <a href="{{ route('journals.index') }}" wire:navigate class="text-sm" style="color:var(--brand); font-weight:600;">Lihat semua</a>
            </div>

            @forelse ($recentJournals as $journal)
                <div class="j-row" style="padding:0.9rem 1.15rem;">
                    <div class="flex items-center justify-between" style="gap:0.75rem;">
                        <p style="font-size:0.75rem; font-weight:600; color:var(--text-muted);">
                            <i class="fa-regular fa-calendar-check" style="margin-right:0.35rem;"></i>
                            {{ \Illuminate\Support\Carbon::parse($journal->date)->translatedFormat('l, d F Y') }}
                        </p>
                        @php($count = $journal->attachments()->count())
                        @if ($count)
                            <span class="badge badge-neutral" style="flex-shrink:0;">
                                <i class="fa-solid fa-paperclip"></i> {{ $count }}
                            </span>
                        @endif
                    </div>
                    <p class="text-sm clamp-2" style="margin-top:0.4rem; color:var(--text-body);">{{ $journal->activity }}</p>
                </div>
            @empty
                <div style="padding:2.5rem 1.15rem; text-align:center;">
                    <i class="fa-regular fa-folder-open" style="font-size:1.5rem; color:var(--text-faint);"></i>
                    <p class="text-sm" style="margin-top:0.5rem; color:var(--text-muted);">
                        Belum ada jurnal. Mulai isi jurnal harianmu.
                    </p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Modal isi jurnal --}}
    <livewire:journals.create />

    {{-- ===== Modal: Ubah Profil (foto profil & warna kartu beranda) ===== --}}
    @if ($intern)
        <div
            x-data
            x-show="$wire.showProfileModal"
            x-cloak
            x-transition.opacity
            @keydown.escape.window="$wire.showProfileModal && $wire.closeProfileModal()"
            style="position:fixed; inset:0; z-index:50; display:flex; align-items:center; justify-content:center; padding:1.25rem; overflow-y:auto; background:rgba(2,6,23,0.55);"
        >
            <div @click.outside="$wire.closeProfileModal()" x-show="$wire.showProfileModal" x-transition
                 class="surface-card" style="width:100%; max-width:26rem; margin:auto; padding:0;">
                <div class="flex items-center justify-between"
                     style="padding:1.1rem 1.35rem; border-bottom:1px solid var(--border-soft);">
                    <h2 style="font-size:1.05rem; font-weight:700;">Ubah Profil</h2>
                    <button type="button" wire:click="closeProfileModal" class="theme-toggle" aria-label="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div style="padding:1.35rem;">
                    <div style="margin-bottom:1.2rem;" x-data="avatarCropper" x-effect="! $wire.showProfileModal && reset()">
                        <label class="form-label">Foto Profil</label>

                        {{-- Stage "idle": belum pilih foto baru — tampilkan foto tersimpan/placeholder --}}
                        <div x-show="stage === 'idle'" class="flex items-center" style="gap:0.85rem;">
                            @if ($intern->avatar_path)
                                <img src="{{ url('storage/' . $intern->avatar_path) }}" alt="Foto profil"
                                     style="width:3.5rem; height:3.5rem; border-radius:9999px; object-fit:cover; flex-shrink:0;">
                            @else
                                <div style="width:3.5rem; height:3.5rem; border-radius:9999px; background:var(--surface-alt); display:flex; align-items:center; justify-content:center; color:var(--text-faint); flex-shrink:0;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            @endif
                            <button type="button" @click="$refs.avatarFile.click()" class="btn-ghost" style="padding:0.45rem 0.8rem;">
                                <i class="fa-solid fa-camera"></i> Pilih Foto
                            </button>
                            <input type="file" x-ref="avatarFile" accept="image/*" @change="onFile($event)" style="display:none;">
                        </div>

                        {{-- Stage "done": foto otomatis di-crop tengah jadi bulat, belum tersimpan sampai klik Simpan --}}
                        <div x-show="stage === 'done'" x-cloak class="flex items-center" style="gap:0.85rem;">
                            <img :src="preview" alt="Pratinjau foto baru" style="width:3.5rem; height:3.5rem; border-radius:9999px; object-fit:cover; flex-shrink:0;">
                            <button type="button" @click="changePhoto()" class="btn-ghost" style="padding:0.45rem 0.8rem;">
                                <i class="fa-solid fa-pen"></i> Ganti Foto
                            </button>
                        </div>

                        @error('avatarDataUrl')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom:0.5rem;">
                        <label for="p-color" class="form-label">Warna Kartu Beranda</label>
                        <div class="flex items-center" style="gap:0.6rem;">
                            <input id="p-color" type="color" wire:model="dashboardColor"
                                   value="{{ $dashboardColor !== '' ? $dashboardColor : '#0b47a1' }}"
                                   style="width:2.6rem; height:2.6rem; padding:0; border:1px solid var(--border); border-radius:8px; cursor:pointer;">
                            <span class="text-sm" style="color:var(--text-muted);">
                                {{ $dashboardColor !== '' ? $dashboardColor : 'Warna default' }}
                            </span>
                            @if ($dashboardColor !== '')
                                <button type="button" wire:click="resetDashboardColor" class="text-sm" style="color:var(--text-muted); text-decoration:underline; margin-left:auto;">
                                    Pakai warna default
                                </button>
                            @endif
                        </div>
                        @error('dashboardColor')
                            <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end" style="gap:0.5rem; padding:1.1rem 1.35rem; border-top:1px solid var(--border-soft);">
                    <button type="button" wire:click="closeProfileModal" class="btn-ghost">Batal</button>
                    <button type="button" wire:click="saveProfile" class="btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    @endif
</div>
