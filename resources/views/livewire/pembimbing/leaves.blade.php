<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Izin Intern</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Konfirmasi pengajuan izin dan sakit dari peserta magang
        </p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem; margin-bottom:1.1rem;">
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Menunggu Konfirmasi</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $pendingCount }}</p>
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
                <option value="pending">Menunggu konfirmasi</option>
                <option value="approved">Disetujui</option>
                <option value="rejected">Ditolak</option>
            </select>
        </div>
    </div>

    {{-- ===== Daftar pengajuan ===== --}}
    <div class="flex" style="flex-direction:column; gap:0.75rem;">
        @forelse ($leaves as $leave)
            <article class="surface-card" style="padding:1.1rem 1.15rem;">
                <div class="flex items-center justify-between" style="gap:0.75rem; flex-wrap:wrap;">
                    <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap;">
                        <span style="font-weight:700; color:var(--text-heading);">{{ $leave->intern->nama ?? 'Peserta dihapus' }}</span>
                        @if ($leave->intern?->unit)
                            <span class="badge badge-neutral"><i class="fa-solid fa-people-group"></i> {{ $leave->intern->unit->name }}</span>
                        @endif
                        <span class="badge badge-neutral" style="text-transform:capitalize;">{{ $leave->type }}</span>
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

                @if ($leave->status === 'pending')
                    <div style="margin-top:0.9rem; padding-top:0.7rem; border-top:1px solid var(--border-soft);">
                        @if ($rejecting === $leave->id)
                            <div style="margin-bottom:0.6rem;">
                                <textarea wire:model="reviewNote" rows="2" class="form-input" placeholder="Catatan penolakan (opsional)..."></textarea>
                            </div>
                            <div class="flex items-center" style="gap:0.5rem;">
                                <button type="button" wire:click="reject({{ $leave->id }})" class="btn-primary" style="padding:0.4rem 0.75rem; background:#dc2626; border-color:#dc2626;">
                                    <i class="fa-solid fa-xmark"></i> Konfirmasi Tolak
                                </button>
                                <button type="button" wire:click="cancelReject" class="btn-ghost" style="padding:0.4rem 0.75rem;">Batal</button>
                            </div>
                        @else
                            <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap;">
                                <button type="button" wire:click="approve({{ $leave->id }})" class="btn-primary" style="padding:0.4rem 0.75rem;">
                                    <i class="fa-solid fa-check"></i> Setujui
                                </button>
                                <button type="button" wire:click="startReject({{ $leave->id }})" class="btn-ghost" style="padding:0.4rem 0.75rem; color:#dc2626;">
                                    <i class="fa-solid fa-xmark"></i> Tolak
                                </button>
                            </div>
                        @endif
                    </div>
                @else
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
                    Belum ada pengajuan izin yang cocok dengan filter.
                </p>
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
</div>
