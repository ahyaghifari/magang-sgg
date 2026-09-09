@php
    use Illuminate\Support\Carbon;

    $fmtTime = fn ($t) => $t ? Carbon::parse($t)->format('H:i') : '—';
    $fmtDur = fn ($m) => $m === null ? '—' : intdiv((int) $m, 60) . 'j ' . ((int) $m % 60) . 'm';
    $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
@endphp

<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Presensi</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Rekap kehadiran dari mesin sidik jari &middot; jam kerja WITA
        </p>
    </div>

    @if (! $intern)
        <div class="surface-card flex items-center" style="gap:0.85rem; padding:1rem 1.15rem;">
            <i class="fa-solid fa-circle-info" style="color:#d97706; font-size:1.1rem;"></i>
            <p class="text-sm" style="color:var(--text-body);">
                Akun kamu belum terhubung dengan data <strong>Intern</strong>. Hubungi admin.
            </p>
        </div>
    @elseif (blank($intern->nip))
        <div class="surface-card flex items-center" style="gap:0.85rem; padding:1rem 1.15rem;">
            <i class="fa-solid fa-fingerprint" style="color:#d97706; font-size:1.1rem;"></i>
            <p class="text-sm" style="color:var(--text-body);">
                <strong>NIP kamu belum diatur.</strong> Presensi dari mesin sidik jari belum bisa
                dicocokkan sampai admin mengisi NIP pada data internmu.
            </p>
        </div>
    @else
        {{-- ===== Ringkasan bulan ini ===== --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem;">
            <div class="stat-card" style="padding:1.1rem 1.15rem;">
                <span class="stat-card-deco"></span>
                <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Hadir Bulan Ini</p>
                <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $monthStats['hadir'] }}</p>
            </div>
            <div class="stat-card" style="padding:1.1rem 1.15rem;">
                <span class="stat-card-deco"></span>
                <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Hari Terlambat</p>
                <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:{{ $monthStats['telat'] ? '#b45309' : 'var(--brand)' }};">{{ $monthStats['telat'] }}</p>
            </div>
            <div class="stat-card" style="padding:1.1rem 1.15rem;">
                <span class="stat-card-deco"></span>
                <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Total Menit Telat</p>
                <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:{{ $monthStats['menit_telat'] ? '#b45309' : 'var(--brand)' }};">{{ $monthStats['menit_telat'] }}</p>
            </div>
        </div>

        {{-- ===== Hari ini ===== --}}
        <div class="surface-card" style="padding:1.25rem 1.25rem; margin-top:0.85rem;">
            <p style="font-size:0.8rem; font-weight:700; color:var(--brand);">
                <i class="fa-regular fa-calendar-check" style="margin-right:0.4rem;"></i>
                {{ $now->translatedFormat('l, d F Y') }} &middot; {{ $now->format('H:i') }} WITA
            </p>

            @php($libur = ! $schedule || $schedule->is_off_day)

            <p class="text-sm" style="color:var(--text-muted); margin-top:0.25rem;">
                @if ($libur)
                    Hari ini libur.
                @else
                    Jadwal {{ $fmtTime($schedule->start_time) }}–{{ $fmtTime($schedule->end_time) }}
                @endif
            </p>

            <div style="margin-top:0.9rem;">
                @if ($today)
                    <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap;">
                        <span class="badge badge-neutral">
                            <i class="fa-solid fa-right-to-bracket"></i> Masuk {{ $fmtTime($today->check_in_time) }}
                        </span>
                        <span class="badge badge-neutral">
                            <i class="fa-solid fa-right-from-bracket"></i> Pulang {{ $fmtTime($today->check_out_time) }}
                        </span>
                        @if ($today->late_minutes > 0)
                            <span class="badge" style="background:#fef3c7; color:#92400e;">Telat {{ $today->late_minutes }} mnt</span>
                        @elseif ($today->check_in_time)
                            <span class="badge" style="background:#dcfce7; color:#166534;">Tepat waktu</span>
                        @endif
                        @if ($today->early_leave_minutes > 0)
                            <span class="badge" style="background:#ffedd5; color:#9a3412;">Pulang cepat {{ $today->early_leave_minutes }} mnt</span>
                        @endif
                        @if ($today->out_of_window)
                            <span class="badge" style="background:#e5e7eb; color:#374151;">Di luar jam wajar</span>
                        @endif
                    </div>
                @elseif ($libur)
                    <span class="text-sm" style="color:var(--text-muted);"><i class="fa-solid fa-mug-hot"></i> Tidak ada presensi.</span>
                @else
                    <span class="text-sm" style="color:var(--text-faint);">Belum ada tap sidik jari terekam hari ini.</span>
                @endif

                @if ($todayTaps->isNotEmpty())
                    <div class="flex" style="flex-wrap:wrap; gap:0.35rem; margin-top:0.7rem;">
                        <span class="text-sm" style="color:var(--text-muted); font-weight:600;">Tap hari ini:</span>
                        @foreach ($todayTaps as $tap)
                            <span class="badge badge-neutral" style="font-variant-numeric:tabular-nums;">
                                {{ Carbon::parse($tap->scanned_at)->format('H:i:s') }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== Riwayat ===== --}}
        <h2 class="text-sm" style="font-weight:700; color:var(--text-heading); margin:1.6rem 0 0.7rem;">Riwayat Presensi</h2>

        <div class="flex" style="flex-direction:column; gap:0.6rem;">
            @forelse ($records as $row)
                <article class="surface-card flex items-center justify-between"
                         style="padding:0.85rem 1.1rem; gap:0.75rem; flex-wrap:wrap;">
                    <div style="min-width:0;">
                        <p style="font-size:0.8rem; font-weight:700; color:var(--text-heading);">
                            {{ Carbon::parse($row->date)->translatedFormat('l, d M Y') }}
                        </p>
                        <p class="text-sm" style="color:var(--text-muted); margin-top:0.15rem; font-variant-numeric:tabular-nums;">
                            Masuk {{ $fmtTime($row->check_in_time) }} &middot;
                            Pulang {{ $fmtTime($row->check_out_time) }} &middot;
                            Kerja {{ $fmtDur($row->working_minutes) }}
                        </p>
                    </div>
                    <div class="flex items-center" style="gap:0.4rem; flex-wrap:wrap;">
                        @if ($row->late_minutes > 0)
                            <span class="badge" style="background:#fef3c7; color:#92400e;">Telat {{ $row->late_minutes }}m</span>
                        @elseif ($row->check_in_time)
                            <span class="badge" style="background:#dcfce7; color:#166534;">Tepat waktu</span>
                        @endif
                        @if ($row->early_leave_minutes > 0)
                            <span class="badge" style="background:#ffedd5; color:#9a3412;">Pulang cepat {{ $row->early_leave_minutes }}m</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="surface-card" style="padding:2.5rem 1.15rem; text-align:center;">
                    <i class="fa-regular fa-clock" style="font-size:1.6rem; color:var(--text-faint);"></i>
                    <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                        Belum ada data presensi. Pastikan NIP-mu benar dan kamu sudah tap di mesin sidik jari.
                    </p>
                </div>
            @endforelse
        </div>

        @if ($records && $records->hasPages())
            <div class="flex items-center justify-between" style="margin-top:1.25rem;">
                <button wire:click="previousPage" class="btn-ghost" @disabled($records->onFirstPage())>
                    <i class="fa-solid fa-chevron-left"></i> Sebelumnya
                </button>
                <span style="font-size:0.8rem; color:var(--text-muted);">
                    Halaman {{ $records->currentPage() }} dari {{ $records->lastPage() }}
                </span>
                <button wire:click="nextPage" class="btn-ghost" @disabled(! $records->hasMorePages())>
                    Berikutnya <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        @endif
    @endif
</div>
