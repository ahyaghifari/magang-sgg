<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sertifikat PKL - {{ $intern->nama }}</title>
<style>
    /*
     * Dibuat khusus untuk DomPDF (bukan browser) — sengaja TIDAK memakai flexbox
     * atau aspect-ratio karena tidak didukung DomPDF. Semua layout pakai posisi
     * absolut di atas kanvas ukuran tetap (280mm x 196mm, rasio sama dengan
     * rancangan awal 1000x700px). Font pakai keluarga generik (serif/sans-serif)
     * supaya DomPDF pakai font bawaannya sendiri, tidak perlu ambil font dari luar.
     */
    @page { margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: sans-serif; }

    :root {
        --navy: #042c6c;
        --navy2: #0b47a1;
        --green: #1c8a4d;
        --magenta: #c74ba0;
        --slate: #334155;
        --slate-soft: #64748b;
    }

    .cert {
        position: relative;
        width: 280mm;
        height: 196mm;
        background: #ffffff;
        overflow: hidden;
    }

    .stripe {
        position: absolute; left: 0; right: 0; top: 0; height: 2.8mm;
        background: #042c6c;
    }
    .stripe-green { position: absolute; left: 61.6%; right: 22%; top: 0; height: 2.8mm; background: #1c8a4d; }
    .stripe-magenta { position: absolute; left: 78%; right: 0; top: 0; height: 2.8mm; background: #c74ba0; }

    .arc { position: absolute; border-radius: 50%; }
    .arc-1 { width: 145.6mm; height: 145.6mm; left: -72.8mm; top: -72.8mm; border: 9.5mm solid var(--navy); opacity: .07; }
    .arc-2 { width: 117.6mm; height: 117.6mm; left: -56mm; top: -56mm; border: 7.3mm solid var(--green); opacity: .10; }
    .arc-3 { width: 95.2mm; height: 95.2mm; left: -42mm; top: -42mm; border: 5.6mm solid var(--magenta); opacity: .10; }
    .arc-4 { width: 128.8mm; height: 128.8mm; right: -64.4mm; bottom: -64.4mm; border: 8.4mm solid var(--navy); opacity: .06; }
    .arc-5 { width: 100.8mm; height: 100.8mm; right: -47.6mm; bottom: -47.6mm; border: 6.2mm solid var(--green); opacity: .09; }

    .brand { position: absolute; top: 16mm; left: 20mm; width: 100mm; }
    .brand img { height: 12mm; }
    .brand-text { font-size: 8pt; letter-spacing: 1px; color: var(--slate-soft); font-weight: bold; }
    .brand-text b { display: block; font-size: 10pt; color: var(--navy); }

    .kicker { position: absolute; top: 16mm; right: 20mm; width: 80mm; text-align: right; font-size: 9pt; color: var(--slate-soft); font-weight: bold; }

    .title-block { position: absolute; top: 40mm; left: 20mm; right: 20mm; text-align: center; }
    .eyebrow { font-size: 10pt; color: var(--green); font-weight: bold; letter-spacing: 1px; }
    .title-block h1 { font-size: 26pt; margin: 3mm 0 0; color: var(--navy); font-weight: bold; }
    .given { font-size: 10pt; color: var(--slate); margin-top: 6mm; }

    .name {
        position: absolute; top: 78mm; left: 40mm; right: 40mm;
        font-family: serif; font-style: italic; font-size: 30pt; font-weight: bold;
        color: var(--navy2); text-align: center;
        border-bottom: 1pt solid #dbe4f3; padding-bottom: 4mm;
    }
    .meta { position: absolute; top: 95mm; left: 40mm; right: 40mm; text-align: center; font-size: 9pt; color: var(--slate-soft); }

    .body-text { position: absolute; top: 104mm; left: 45mm; right: 45mm; text-align: center; font-size: 10pt; line-height: 1.6; color: var(--slate); }
    .body-text b { color: var(--navy); }

    .sign { position: absolute; bottom: 14mm; width: 70mm; text-align: center; }
    .sign-left { left: 25mm; }
    .sign-right { right: 25mm; }
    .sign .line { border-top: 1pt solid #94a3b8; margin-top: 14mm; padding-top: 2mm; }
    .sign .who { font-weight: bold; color: var(--navy); font-size: 10pt; }
    .sign .role { font-size: 8pt; color: var(--slate-soft); }

    .no { position: absolute; bottom: 6mm; left: 20mm; font-size: 7pt; color: var(--slate-soft); letter-spacing: 0.5px; }
</style>
</head>
<body>
    <div class="cert">
        <div class="stripe"></div>
        <div class="stripe-green"></div>
        <div class="stripe-magenta"></div>
        <div class="arc arc-1"></div>
        <div class="arc arc-2"></div>
        <div class="arc arc-3"></div>
        <div class="arc arc-4"></div>
        <div class="arc arc-5"></div>

        <div class="brand">
            @if ($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="Syifa Global Group">
            @endif
            <div class="brand-text"><b>SYIFA GLOBAL GROUP</b>INTERNSHIP</div>
        </div>

        <div class="kicker">Diterbitkan {{ $tanggalTerbit }}</div>

        <div class="title-block">
            <div class="eyebrow">PRAKTIK KERJA LAPANGAN / MAGANG</div>
            <h1>SERTIFIKAT PENGHARGAAN</h1>
            <div class="given">Dengan bangga diberikan kepada:</div>
        </div>

        <div class="name">{{ $intern->nama }}</div>
        <div class="meta">
            {{ $intern->institusi->name ?? '-' }}
            @if ($intern->unit)
                &middot; {{ $intern->unit->company->name ?? '' }} — {{ $intern->unit->name }}
            @endif
        </div>

        <div class="body-text">Atas partisipasi dan dedikasinya dalam menyelesaikan program <b>Praktik Kerja Lapangan (PKL)</b> di <b>{{ $intern->unit->company->name ?? 'Syifa Global Group' }}</b>@if ($intern->tanggal_mulai && $intern->tanggal_selesai), terhitung sejak <b>{{ $intern->tanggal_mulai->translatedFormat('d F Y') }}</b> sampai dengan <b>{{ $intern->tanggal_selesai->translatedFormat('d F Y') }}</b>@endif. Semoga pengalaman ini menjadi bekal yang bermanfaat bagi pengembangan diri dan karier ke depan.</div>

        <div class="sign sign-left">
            <div class="line">
                <div class="who">{{ $intern->pembimbing->name ?? '..............................' }}</div>
                <div class="role">Pembimbing Lapangan</div>
            </div>
        </div>

        <div class="sign sign-right">
            <div class="line">
                <div class="who">{{ $pimpinan->name ?? '..............................' }}</div>
                <div class="role">Pimpinan Perusahaan</div>
            </div>
        </div>

        <div class="no">No. Sertifikat: {{ $nomor }}</div>
    </div>
</body>
</html>
