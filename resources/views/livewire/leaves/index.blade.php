<div>
    <div class="flex items-center justify-between" style="gap:1rem; margin-bottom:1.4rem;">
        <div>
            <h1 class="portal-title">Izin</h1>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
                Ajukan izin atau sakit tidak masuk — menunggu konfirmasi pembimbing
            </p>
        </div>
        @if ($intern)
            <button type="button" wire:click="$dispatch('open-leave-modal')" class="btn-primary" style="flex-shrink:0;">
                <i class="fa-solid fa-plus"></i>
                <span>Ajukan Izin</span>
            </button>
        @endif
    </div>

    <div x-data="{ show: false }" x-cloak
         x-on:leave-saved.window="show = true; setTimeout(() => show = false, 4000)"
         x-show="show" x-transition
         class="surface-card flex items-center"
         style="gap:0.7rem; padding:0.8rem 1rem; margin-bottom:1rem; border-color:#a7f3d0;">
        <i class="fa-solid fa-circle-check" style="color:var(--brand-success);"></i>
        <span class="text-sm" style="color:var(--text-body);">Pengajuan izin berhasil dikirim, menunggu konfirmasi pembimbing.</span>
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
        <div class="flex" style="flex-direction:column; gap:0.85rem;">
            @forelse ($leaves as $leave)
                <article class="surface-card" style="padding:1.1rem 1.15rem;">
                    <div class="flex items-center justify-between" style="gap:0.75rem; flex-wrap:wrap;">
                        <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap;">
                            <span class="badge badge-neutral" style="text-transform:capitalize;">
                                <i class="fa-solid fa-{{ $leave->type === 'sakit' ? 'kit-medical' : 'calendar-xmark' }}"></i>
                                {{ $leave->type }}
                            </span>
                            @if ($leave->status === 'approved')
                                <span class="badge" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-circle-check"></i> Disetujui</span>
                            @elseif ($leave->status === 'rejected')
                                <span class="badge" style="background:#fee2e2; color:#b91c1c;"><i class="fa-solid fa-circle-xmark"></i> Ditolak</span>
                            @else
                                <span class="badge" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-clock"></i> Menunggu konfirmasi</span>
                            @endif
                        </div>
                    </div>

                    <p class="text-sm" style="margin-top:0.55rem; font-weight:600; color:var(--text-heading);">
                        <i class="fa-regular fa-calendar"></i>
                        {{ \Illuminate\Support\Carbon::parse($leave->start_date)->translatedFormat('d F Y') }}
                        @if (! \Illuminate\Support\Carbon::parse($leave->start_date)->isSameDay($leave->end_date))
                            &ndash; {{ \Illuminate\Support\Carbon::parse($leave->end_date)->translatedFormat('d F Y') }}
                        @endif
                    </p>

                    <p class="text-sm" style="margin-top:0.4rem; color:var(--text-body); white-space:pre-line;">{{ $leave->reason }}</p>

                    @if ($leave->attachment_path)
                        <a href="{{ url('storage/' . $leave->attachment_path) }}" target="_blank" rel="noopener"
                           class="badge badge-neutral" style="text-decoration:none; margin-top:0.6rem;">
                            <i class="fa-solid fa-paperclip"></i> Lihat lampiran
                        </a>
                    @endif

                    @if ($leave->status !== 'pending')
                        <div style="margin-top:0.85rem; padding-top:0.7rem; border-top:1px solid var(--border-soft);">
                            <p class="text-sm" style="color:var(--text-muted);">
                                Dikonfirmasi oleh <strong>{{ $leave->reviewer->name ?? '—' }}</strong>
                                @if ($leave->reviewed_at)
                                    · {{ \Illuminate\Support\Carbon::parse($leave->reviewed_at)->translatedFormat('d F Y H:i') }}
                                @endif
                            </p>
                            @if ($leave->review_note)
                                <p class="text-sm" style="margin-top:0.3rem; color:var(--text-body);">
                                    <i class="fa-solid fa-quote-left" style="color:var(--text-faint); margin-right:0.3rem;"></i>{{ $leave->review_note }}
                                </p>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="surface-card" style="padding:2.75rem 1.15rem; text-align:center;">
                    <i class="fa-regular fa-folder-open" style="font-size:1.6rem; color:var(--text-faint);"></i>
                    <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                        Belum ada pengajuan izin.
                    </p>
                    <button type="button" wire:click="$dispatch('open-leave-modal')" class="btn-primary" style="margin-top:1rem;">
                        <i class="fa-solid fa-plus"></i>
                        <span>Ajukan Izin</span>
                    </button>
                </div>
            @endforelse
        </div>

        @if ($leaves->hasPages())
            <div class="flex items-center justify-between" style="margin-top:1.25rem;">
                <button wire:click="previousPage" class="btn-ghost" @disabled($leaves->onFirstPage())>
                    <i class="fa-solid fa-chevron-left"></i> Sebelumnya
                </button>
                <span style="font-size:0.8rem; color:var(--text-muted);">
                    Halaman {{ $leaves->currentPage() }} dari {{ $leaves->lastPage() }}
                </span>
                <button wire:click="nextPage" class="btn-ghost" @disabled(! $leaves->hasMorePages())>
                    Berikutnya <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        @endif
    @endif

    {{-- Modal ajukan izin --}}
    <livewire:leaves.create />
</div>
