@php
    use Illuminate\Support\Carbon;

    $fmtTime = fn ($t) => $t ? Carbon::parse($t)->format('H:i') : '—';
    $fmtDur = fn ($m) => $m === null ? '—' : intdiv((int) $m, 60) . 'j ' . ((int) $m % 60) . 'm';
@endphp

<div>
    <div style="margin-bottom:1.4rem;">
        <h1 class="portal-title">Presensi Intern</h1>
        <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
            Rekap kehadiran seluruh peserta magang dari mesin sidik jari &middot; jam kerja WITA
        </p>
    </div>

    {{-- ===== Ringkasan ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:0.85rem; margin-bottom:1.1rem;">
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Baris Rekap</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:var(--brand);">{{ $summary['total'] }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Hari Terlambat</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:{{ $summary['telat'] ? '#b45309' : 'var(--brand)' }};">{{ $summary['telat'] }}</p>
        </div>
        <div class="stat-card" style="padding:1.1rem 1.15rem;">
            <span class="stat-card-deco"></span>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:600;">Total Menit Telat</p>
            <p style="margin-top:0.3rem; font-size:1.65rem; font-weight:700; color:{{ $summary['menit_telat'] ? '#b45309' : 'var(--brand)' }};">{{ $summary['menit_telat'] }}</p>
        </div>
    </div>

    {{-- ===== Filter ===== --}}
    <div class="surface-card" style="padding:0.9rem 1rem; margin-bottom:1rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.75rem;">
        <div>
            <label for="f-intern" class="form-label">Peserta</label>
            <select id="f-intern" wire:model.live="internId" class="form-input">
                <option value="">Semua peserta</option>
                @foreach ($interns as $i)
                    <option value="{{ $i->id }}">{{ $i->nama }}{{ $i->nip ? ' — ' . $i->nip : ' (NIP kosong)' }}</option>
                @endforeach
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
        @if ($internId !== '' || $dateFrom !== '' || $dateTo !== '')
            <div style="display:flex; align-items:flex-end;">
                <button type="button" wire:click="resetFilters" class="btn-ghost" style="padding:0.5rem 0.85rem;">
                    <i class="fa-solid fa-xmark"></i> Reset
                </button>
            </div>
        @endif
    </div>

    {{-- ===== Daftar ===== --}}
    <div class="flex" style="flex-direction:column; gap:0.6rem;">
        @forelse ($records as $row)
            <article class="surface-card flex items-center justify-between"
                     style="padding:0.85rem 1.1rem; gap:0.75rem; flex-wrap:wrap;">
                <div style="min-width:0;">
                    <p style="font-size:0.8rem; font-weight:700; color:var(--text-heading);">
                        {{ $row->intern->nama ?? 'NIP ' . $row->nip }}
                        @if ($row->intern?->unit)
                            <span class="badge badge-neutral" style="margin-left:0.35rem;">
                                <i class="fa-solid fa-people-group"></i> {{ $row->intern->unit->name }}
                            </span>
                        @endif
                    </p>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.15rem; font-variant-numeric:tabular-nums;">
                        {{ Carbon::parse($row->date)->translatedFormat('l, d M Y') }} &middot;
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
                    @if ($row->out_of_window)
                        <span class="badge" style="background:#e5e7eb; color:#374151;">Di luar jam wajar</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="surface-card" style="padding:2.5rem 1.15rem; text-align:center;">
                <i class="fa-regular fa-clock" style="font-size:1.6rem; color:var(--text-faint);"></i>
                <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                    Belum ada data presensi yang cocok dengan filter.
                </p>
            </div>
        @endforelse
    </div>

    @if ($records->hasPages())
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
</div>
