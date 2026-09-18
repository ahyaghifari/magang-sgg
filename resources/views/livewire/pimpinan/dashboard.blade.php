@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $fmtTime = fn ($t) => $t ? Carbon::parse($t)->format('H:i') : '—';
@endphp

<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Dashboard Pimpinan</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Ringkasan kegiatan, presensi, jurnal, dan izin seluruh peserta magang &middot; {{ $now->translatedFormat('l, d F Y') }}
        </p>
    </div>

    {{-- ===== Ringkasan ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem; margin-bottom:1.6rem;">
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Peserta Magang</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $totalInterns }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Hadir Hari Ini</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:#15803d;">{{ $hadirHariIni }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Telat Hari Ini</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:{{ $telatHariIni ? '#b45309' : 'var(--brand)' }};">{{ $telatHariIni }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Jurnal Bulan Ini</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $journalsThisMonth }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Izin Menunggu</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:{{ $izinPendingCount ? '#b45309' : 'var(--brand)' }};">{{ $izinPendingCount }}</p>
        </div>
    </div>

    {{-- ===== Kegiatan & Jurnal (semua) ===== --}}
    <section style="margin-bottom:1.75rem;">
        <h2 style="font-size:1rem; font-weight:700; color:var(--text-heading); margin-bottom:0.7rem;">
            <i class="fa-solid fa-list-check" style="color:var(--brand); margin-right:0.35rem;"></i>
            Semua Kegiatan &amp; Jurnal
        </h2>

        <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:0.85rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.75rem;">
            <div>
                <label for="k-intern" class="form-label">Peserta</label>
                <select id="k-intern" wire:model.live="kegiatanInternId" class="form-input">
                    <option value="">Semua peserta</option>
                    @foreach ($interns as $i)
                        <option value="{{ $i->id }}">{{ $i->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="k-from" class="form-label">Dari tanggal</label>
                <input id="k-from" type="date" wire:model.live="kegiatanDateFrom" class="form-input">
            </div>
            <div>
                <label for="k-to" class="form-label">Sampai tanggal</label>
                <input id="k-to" type="date" wire:model.live="kegiatanDateTo" class="form-input">
            </div>
            @if ($kegiatanInternId !== '' || $kegiatanDateFrom !== '' || $kegiatanDateTo !== '')
                <div style="display:flex; align-items:flex-end;">
                    <button type="button" wire:click="resetKegiatanFilter" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                        <i class="fa-solid fa-xmark"></i> Reset
                    </button>
                </div>
            @endif
        </div>

        <div class="flex" style="flex-direction:column; gap:0.6rem;">
            @forelse ($kegiatan as $j)
                <div class="surface-card" style="padding:0.85rem 1rem;">
                    <div class="flex items-center justify-between" style="gap:0.5rem; flex-wrap:wrap;">
                        <span style="font-weight:700; font-size:0.85rem; color:var(--text-heading);">{{ $j->intern->nama ?? 'Peserta dihapus' }}</span>
                        <span class="text-sm" style="color:var(--text-muted); font-variant-numeric:tabular-nums;">{{ Carbon::parse($j->date)->translatedFormat('d M Y') }}</span>
                    </div>
                    @if ($j->intern?->unit)
                        <span class="badge badge-neutral" style="margin-top:0.35rem;"><i class="fa-solid fa-people-group"></i> {{ $j->intern->unit->name }}</span>
                    @endif
                    <p class="text-sm" style="margin-top:0.4rem; color:var(--text-body); white-space:pre-line;">{{ $j->activity }}</p>
                </div>
            @empty
                <div class="surface-card" style="padding:2.5rem 1.15rem; text-align:center;">
                    <i class="fa-regular fa-folder-open" style="font-size:1.6rem; color:var(--text-faint);"></i>
                    <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">Belum ada kegiatan/jurnal yang cocok dengan filter.</p>
                </div>
            @endforelse
        </div>

        @if ($kegiatan->hasPages())
            <div class="flex items-center justify-between" style="margin-top:1rem;">
                <button wire:click="previousPage('kegiatanPage')" class="btn-ghost" @disabled($kegiatan->onFirstPage())>
                    <i class="fa-solid fa-chevron-left"></i> Sebelumnya
                </button>
                <span style="font-size:0.8rem; color:var(--text-muted);">Halaman {{ $kegiatan->currentPage() }} dari {{ $kegiatan->lastPage() }}</span>
                <button wire:click="nextPage('kegiatanPage')" class="btn-ghost" @disabled(! $kegiatan->hasMorePages())>
                    Berikutnya <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        @endif
    </section>

    {{-- ===== Presensi (semua) ===== --}}
    <section style="margin-bottom:1.75rem;">
        <h2 style="font-size:1rem; font-weight:700; color:var(--text-heading); margin-bottom:0.7rem;">
            <i class="fa-solid fa-fingerprint" style="color:var(--brand); margin-right:0.35rem;"></i>
            Semua Presensi &mdash; Masuk &amp; Telat
        </h2>

        <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:0.85rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.75rem;">
            <div>
                <label for="p-intern" class="form-label">Peserta</label>
                <select id="p-intern" wire:model.live="presensiInternId" class="form-input">
                    <option value="">Semua peserta</option>
                    @foreach ($interns as $i)
                        <option value="{{ $i->id }}">{{ $i->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="p-from" class="form-label">Dari tanggal</label>
                <input id="p-from" type="date" wire:model.live="presensiDateFrom" class="form-input">
            </div>
            <div>
                <label for="p-to" class="form-label">Sampai tanggal</label>
                <input id="p-to" type="date" wire:model.live="presensiDateTo" class="form-input">
            </div>
            <div style="display:flex; align-items:flex-end;">
                <button type="button" wire:click="resetPresensiFilter" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                    <i class="fa-solid fa-rotate-left"></i> Hari ini
                </button>
            </div>
        </div>

        <div class="flex" style="flex-direction:column; gap:0.55rem;">
            @forelse ($presensi as $row)
                <div class="surface-card flex items-center justify-between" style="padding:0.8rem 1rem; gap:0.75rem; flex-wrap:wrap;">
                    <div style="min-width:0;">
                        <p style="font-size:0.82rem; font-weight:700; color:var(--text-heading);">
                            {{ $row->intern->nama ?? 'NIP ' . $row->nip }}
                            @if ($row->intern?->unit)
                                <span class="badge badge-neutral" style="margin-left:0.35rem;"><i class="fa-solid fa-people-group"></i> {{ $row->intern->unit->name }}</span>
                            @endif
                        </p>
                        <p class="text-sm" style="color:var(--text-muted); margin-top:0.15rem; font-variant-numeric:tabular-nums;">
                            {{ Carbon::parse($row->date)->translatedFormat('l, d M Y') }} &middot;
                            Masuk {{ $fmtTime($row->check_in_time) }} &middot;
                            Pulang {{ $fmtTime($row->check_out_time) }}
                        </p>
                    </div>
                    <div class="flex items-center" style="gap:0.4rem; flex-wrap:wrap;">
                        @if ($row->late_minutes > 0)
                            <span class="badge" style="background:#fef3c7; color:#92400e;">Telat {{ $row->late_minutes }}m</span>
                        @elseif ($row->check_in_time)
                            <span class="badge" style="background:#dcfce7; color:#166534;">Tepat waktu</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="surface-card" style="padding:2.5rem 1.15rem; text-align:center;">
                    <i class="fa-regular fa-clock" style="font-size:1.6rem; color:var(--text-faint);"></i>
                    <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">Belum ada data presensi yang cocok dengan filter.</p>
                </div>
            @endforelse
        </div>

        @if ($presensi->hasPages())
            <div class="flex items-center justify-between" style="margin-top:1rem;">
                <button wire:click="previousPage('presensiPage')" class="btn-ghost" @disabled($presensi->onFirstPage())>
                    <i class="fa-solid fa-chevron-left"></i> Sebelumnya
                </button>
                <span style="font-size:0.8rem; color:var(--text-muted);">Halaman {{ $presensi->currentPage() }} dari {{ $presensi->lastPage() }}</span>
                <button wire:click="nextPage('presensiPage')" class="btn-ghost" @disabled(! $presensi->hasMorePages())>
                    Berikutnya <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        @endif
    </section>

    {{-- ===== Izin (semua) ===== --}}
    <section>
        <h2 style="font-size:1rem; font-weight:700; color:var(--text-heading); margin-bottom:0.7rem;">
            <i class="fa-solid fa-calendar-xmark" style="color:var(--brand); margin-right:0.35rem;"></i>
            Semua Izin Peserta Magang
        </h2>

        <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:0.85rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.75rem;">
            <div>
                <label for="i-intern" class="form-label">Peserta</label>
                <select id="i-intern" wire:model.live="izinInternId" class="form-input">
                    <option value="">Semua peserta</option>
                    @foreach ($interns as $i)
                        <option value="{{ $i->id }}">{{ $i->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="i-status" class="form-label">Status</label>
                <select id="i-status" wire:model.live="izinStatus" class="form-input">
                    <option value="">Semua status</option>
                    <option value="pending">Menunggu konfirmasi</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
            @if ($izinInternId !== '' || $izinStatus !== '')
                <div style="display:flex; align-items:flex-end;">
                    <button type="button" wire:click="resetIzinFilter" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                        <i class="fa-solid fa-xmark"></i> Reset
                    </button>
                </div>
            @endif
        </div>

        <div class="flex" style="flex-direction:column; gap:0.6rem;">
            @forelse ($izin as $leave)
                <div class="surface-card" style="padding:0.9rem 1rem;">
                    <div class="flex items-center justify-between" style="gap:0.5rem; flex-wrap:wrap;">
                        <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap;">
                            <span style="font-weight:700; font-size:0.85rem; color:var(--text-heading);">{{ $leave->intern->nama ?? 'Peserta dihapus' }}</span>
                            @if ($leave->intern?->unit)
                                <span class="badge badge-neutral"><i class="fa-solid fa-people-group"></i> {{ $leave->intern->unit->name }}</span>
                            @endif
                            <span class="badge badge-neutral" style="text-transform:capitalize;">{{ $leave->type }}</span>
                        </div>
                        @if ($leave->status === 'approved')
                            <span class="badge" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-circle-check"></i> Disetujui</span>
                        @elseif ($leave->status === 'rejected')
                            <span class="badge" style="background:#fee2e2; color:#b91c1c;"><i class="fa-solid fa-circle-xmark"></i> Ditolak</span>
                        @else
                            <span class="badge" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-clock"></i> Menunggu konfirmasi</span>
                        @endif
                    </div>
                    <p class="text-sm" style="margin-top:0.5rem; font-weight:600; color:var(--text-heading);">
                        <i class="fa-regular fa-calendar"></i>
                        {{ Carbon::parse($leave->start_date)->translatedFormat('d F Y') }}
                        @if (! Carbon::parse($leave->start_date)->isSameDay($leave->end_date))
                            &ndash; {{ Carbon::parse($leave->end_date)->translatedFormat('d F Y') }}
                        @endif
                    </p>
                    <p class="text-sm" style="margin-top:0.3rem; color:var(--text-body); white-space:pre-line;">{{ $leave->reason }}</p>
                    @if ($leave->status !== 'pending')
                        <p class="text-sm" style="margin-top:0.4rem; color:var(--text-muted);">
                            Dikonfirmasi oleh <strong>{{ $leave->reviewer->name ?? '—' }}</strong>
                            @if ($leave->reviewed_at)
                                &middot; {{ Carbon::parse($leave->reviewed_at)->translatedFormat('d F Y H:i') }}
                            @endif
                        </p>
                    @endif
                </div>
            @empty
                <div class="surface-card" style="padding:2.5rem 1.15rem; text-align:center;">
                    <i class="fa-regular fa-folder-open" style="font-size:1.6rem; color:var(--text-faint);"></i>
                    <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">Belum ada pengajuan izin yang cocok dengan filter.</p>
                </div>
            @endforelse
        </div>

        @if ($izin->hasPages())
            <div class="flex items-center justify-between" style="margin-top:1rem;">
                <button wire:click="previousPage('izinPage')" class="btn-ghost" @disabled($izin->onFirstPage())>
                    <i class="fa-solid fa-chevron-left"></i> Sebelumnya
                </button>
                <span style="font-size:0.8rem; color:var(--text-muted);">Halaman {{ $izin->currentPage() }} dari {{ $izin->lastPage() }}</span>
                <button wire:click="nextPage('izinPage')" class="btn-ghost" @disabled(! $izin->hasMorePages())>
                    Berikutnya <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        @endif
    </section>
</div>
