{{-- Kartu ringkas satu peserta magang (Intern mentor & Dashboard pimpinan). Wajib: ['intern' => $intern] dengan withCount rekap (lihat HasInternDetailModals). --}}
@php($mulai = $intern->tanggal_mulai ? \Illuminate\Support\Carbon::parse($intern->tanggal_mulai) : null)
@php($selesai = $intern->tanggal_selesai ? \Illuminate\Support\Carbon::parse($intern->tanggal_selesai) : null)
@php($sudahSelesai = $selesai && $selesai->isPast())
<article class="surface-card" style="padding:1.1rem 1.15rem;">
    <div class="flex items-start justify-between" style="gap:0.6rem;">
        <div class="flex items-start" style="gap:0.75rem; min-width:0;">
        <x-intern-avatar :intern="$intern" size="2.8rem" />
        <div style="min-width:0;">
            <p style="font-weight:700; color:var(--text-heading);">
                {{ $intern->nama }}
                @if ($intern->nama_panggilan)
                    <span class="text-sm" style="font-weight:400; color:var(--text-muted);">({{ $intern->nama_panggilan }})</span>
                @endif
            </p>
            <div class="flex" style="flex-wrap:wrap; gap:0.35rem; margin-top:0.4rem;">
                @if ($intern->institusi)
                    <span class="badge badge-neutral"><i class="fa-solid fa-school"></i> {{ $intern->institusi->name }}</span>
                @endif
                @if ($intern->unit)
                    <span class="badge badge-neutral"><i class="fa-solid fa-people-group"></i> {{ $intern->unit->name }}</span>
                @endif
            </div>
        </div>
        </div>
        <span class="badge" style="{{ $sudahSelesai ? 'background:#f1f5f9; color:#64748b;' : 'background:#dcfce7; color:#15803d;' }} flex-shrink:0;">
            <i class="fa-solid {{ $sudahSelesai ? 'fa-circle-check' : 'fa-circle-play' }}"></i>
            {{ $sudahSelesai ? 'Selesai' : 'Berjalan' }}
        </span>
    </div>

    <p class="text-sm" style="margin-top:0.7rem; color:var(--text-muted);">
        <i class="fa-regular fa-calendar"></i>
        @if ($mulai && $selesai)
            {{ $mulai->translatedFormat('d F Y') }} &ndash; {{ $selesai->translatedFormat('d F Y') }}
        @else
            Periode magang belum diisi
        @endif
    </p>

    <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:0.6rem; margin-top:0.9rem; padding-top:0.85rem; border-top:1px solid var(--border-soft);">
        <button type="button" wire:click="openJournals({{ $intern->id }})"
                style="text-align:left; background:none; border:0; padding:0; cursor:pointer;">
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.03em; color:var(--text-faint); font-weight:600;">
                <i class="fa-solid fa-book"></i> Jurnal
            </p>
            <p style="margin-top:0.2rem; font-weight:700; color:var(--brand); text-decoration:underline;">{{ $intern->jurnal_count }}</p>
        </button>
        <button type="button" wire:click="openTasks({{ $intern->id }})"
                style="text-align:left; background:none; border:0; padding:0; cursor:pointer;">
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.03em; color:var(--text-faint); font-weight:600;">
                <i class="fa-solid fa-clipboard-list"></i> Tugas
            </p>
            <p style="margin-top:0.2rem; font-weight:700; color:var(--brand); text-decoration:underline;">
                {{ $intern->tugas_selesai_count }}<span style="font-weight:400; text-decoration:none; color:var(--text-muted);">/{{ $intern->tugas_total_count }} selesai</span>
            </p>
        </button>
        <div>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.03em; color:var(--text-faint); font-weight:600;">
                <i class="fa-solid fa-fingerprint"></i> Presensi
            </p>
            <p style="margin-top:0.2rem; font-size:0.82rem; color:var(--text-heading);">
                <span style="color:#15803d; font-weight:700;">{{ $intern->presensi_hadir_count }}</span> hadir &middot;
                <span style="color:#b45309; font-weight:700;">{{ $intern->presensi_telat_count }}</span> telat &middot;
                <span style="color:#b91c1c; font-weight:700;">{{ $intern->presensi_absen_count }}</span> absen
            </p>
        </div>
        <div>
            <p style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.03em; color:var(--text-faint); font-weight:600;">
                <i class="fa-solid fa-calendar-xmark"></i> Izin
            </p>
            <p style="margin-top:0.2rem; font-weight:700; color:var(--text-heading);">
                {{ $intern->izin_approved_count }}<span style="font-weight:400; color:var(--text-muted);">/{{ $intern->izin_total_count }} disetujui</span>
            </p>
        </div>
    </div>
</article>
