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
    <section class="welcome-card" style="padding: 1.5rem;">
        <span class="w-deco-1"></span>
        <span class="w-deco-2"></span>
        <span class="w-deco-3"></span>

        <div style="position: relative; z-index: 1;">
            <p style="font-size:0.8125rem; opacity:0.8;">
                <i class="fa-regular fa-calendar" style="margin-right:0.4rem;"></i>
                {{ \Illuminate\Support\Carbon::now()->translatedFormat('l, d F Y') }}
            </p>
            <p style="margin-top:0.4rem; font-size:1.375rem; font-weight:700; color:#fff;">
                Halo, {{ $intern?->nama ?? $user->name }} 👋
            </p>
            @unless ($intern)
                <p style="margin-top:0.25rem; font-size:0.875rem; opacity:0.85;">
                    Selamat datang di portal Magang Syifa Global Group
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
                </div>
            @endif
        </div>
    </section>

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
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:0.85rem; margin-top:1.25rem;">
            <div class="surface-card" style="padding:0.95rem 1.05rem;">
                <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">
                    <i class="fa-solid fa-building" style="margin-right:0.35rem;"></i>Institusi
                </p>
                <p class="text-sm" style="margin-top:0.35rem; font-weight:600; color:var(--text-heading);">
                    {{ $intern->institusi->name ?? '—' }}
                </p>
            </div>
            <div class="surface-card" style="padding:0.95rem 1.05rem;">
                <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">
                    <i class="fa-solid fa-id-badge" style="margin-right:0.35rem;"></i>Nama Peserta
                </p>
                <p class="text-sm" style="margin-top:0.35rem; font-weight:600; color:var(--text-heading);">
                    {{ $intern->nama }}
                </p>
            </div>
        </div>

        {{-- ===== Stats ===== --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem; margin-top:0.85rem;">
            <div class="stat-card" style="padding:1.1rem 1.15rem;">
                <span class="stat-card-deco"></span>
                <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Total Jurnal</p>
                <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $totalJournals }}</p>
            </div>
            <div class="stat-card" style="padding:1.1rem 1.15rem;">
                <span class="stat-card-deco"></span>
                <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Bulan Ini</p>
                <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $journalsThisMonth }}</p>
            </div>
            <div class="stat-card" style="padding:1.1rem 1.15rem;">
                <span class="stat-card-deco"></span>
                <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Jurnal Terakhir</p>
                <p class="text-sm" style="margin-top:0.55rem; font-weight:600; color:var(--text-heading);">
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
</div>
