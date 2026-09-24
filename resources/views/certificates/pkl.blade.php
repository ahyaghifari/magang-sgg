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
    @page { margin: 0; size: 280mm 196mm; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: sans-serif; }

    /* Warna ditulis literal (bukan CSS var()) — DomPDF tidak konsisten meng-apply
       var() lewat properti shorthand background pada sel tabel (th/td), jadi latar
       header tabel bisa gagal terisi meski warna teksnya tetap muncul. */
    .cert {
        position: relative;
        width: 280mm;
        height: 196mm;
        background-color: #ffffff;
        overflow: hidden;
    }

    .stripe {
        position: absolute; left: 0; right: 0; top: 0; height: 2.8mm;
        background-color: #042c6c;
    }
    .stripe-green { position: absolute; left: 61.6%; right: 22%; top: 0; height: 2.8mm; background-color: #1c8a4d; }
    .stripe-magenta { position: absolute; left: 78%; right: 0; top: 0; height: 2.8mm; background-color: #c74ba0; }

    /* Bingkai ganda berwarna — navy tebal di luar, magenta tipis di dalam. */
    .frame-outer {
        position: absolute; top: 5mm; left: 5mm; right: 5mm; bottom: 5mm;
        border: 1.1mm solid #042c6c; border-radius: 2.5mm;
    }
    .frame-inner {
        position: absolute; top: 6.6mm; left: 6.6mm; right: 6.6mm; bottom: 6.6mm;
        border: 0.35mm solid #c74ba0; border-radius: 1.8mm;
    }

    .arc { position: absolute; border-radius: 50%; }
    .arc-1 { width: 145.6mm; height: 145.6mm; left: -72.8mm; top: -72.8mm; border: 14mm solid #042c6c; opacity: .22; }
    .arc-2 { width: 117.6mm; height: 117.6mm; left: -56mm; top: -56mm; border: 11mm solid #1c8a4d; opacity: .26; }
    .arc-3 { width: 95.2mm; height: 95.2mm; left: -42mm; top: -42mm; border: 8.5mm solid #c74ba0; opacity: .26; }
    .arc-4 { width: 128.8mm; height: 128.8mm; right: -64.4mm; bottom: -64.4mm; border: 12.5mm solid #042c6c; opacity: .20; }
    .arc-5 { width: 100.8mm; height: 100.8mm; right: -47.6mm; bottom: -47.6mm; border: 9.5mm solid #1c8a4d; opacity: .24; }
    .arc-6 { width: 76mm; height: 76mm; right: -34mm; bottom: -34mm; border: 6.8mm solid #c74ba0; opacity: .24; }

    .brand { position: absolute; top: 16mm; left: 20mm; width: 100mm; }
    .brand img { height: 12mm; }
    .brand-text { font-size: 8pt; letter-spacing: 1px; color: #64748b; font-weight: bold; }
    .brand-text b { display: block; font-size: 10pt; color: #042c6c; }

    .title-block { position: absolute; top: 40mm; left: 20mm; right: 20mm; text-align: center; }
    .eyebrow {
        display: inline-block; background-color: #1c8a4d; color: #ffffff;
        font-size: 9pt; font-weight: bold; letter-spacing: 1.5px;
        padding: 1.8mm 7mm; border-radius: 20mm;
    }
    .title-block h1 { font-size: 26pt; margin: 4mm 0 0; color: #042c6c; font-weight: bold; }
    .given { font-size: 10pt; color: #334155; margin-top: 6mm; }

    .name {
        position: absolute; top: 78mm; left: 40mm; right: 40mm;
        font-family: serif; font-style: italic; font-size: 30pt; font-weight: bold;
        color: #0b47a1; text-align: center;
        border-bottom: 1.4pt solid #c74ba0; padding-bottom: 4mm;
    }
    .body-text { position: absolute; top: 104mm; left: 45mm; right: 45mm; text-align: center; font-size: 10pt; line-height: 1.6; color: #334155; }
    .body-text b { color: #042c6c; }

    .sign { position: absolute; bottom: 14mm; width: 70mm; text-align: center; }
    .sign-left { left: 25mm; }
    .sign-right { right: 25mm; }
    .sign .line { border-top: 1pt solid #94a3b8; margin-top: 14mm; padding-top: 2mm; }
    .sign .who { font-weight: bold; color: #042c6c; font-size: 10pt; }
    .sign .role { font-size: 8pt; color: #64748b; }

    .no { position: absolute; bottom: 10mm; left: 25mm; font-size: 7.5pt; }
    .no .no-label { color: #64748b; letter-spacing: 1.2px; font-weight: bold; margin-right: 2mm; }
    .no .no-value { color: #042c6c; font-weight: bold; letter-spacing: 0.4px; }

    /*
     * Halaman 2 — form Appraisal, dicetak di kanvas ukuran PERSIS SAMA (280x196mm)
     * dengan halaman 1 supaya keduanya bisa dicetak bolak-balik di satu lembar fisik
     * yang sama (DomPDF cuma bisa satu ukuran halaman per dokumen, jadi ukurannya
     * harus disamakan). Layout dua-kolom di sini SENGAJA pakai posisi absolut
     * (bukan tabel dua-kolom) karena nested table dengan lebar mm/percentage di
     * dalam <td> lain terbukti tidak reliable di DomPDF (kolom kanan meleset lebar
     * & posisinya) — pola posisi absolut ini sudah terbukti stabil di halaman 1 (.cert).
     */
    .appraisal {
        page-break-before: always;
        position: relative;
        width: 280mm;
        height: 196mm;
        overflow: hidden;
        font-size: 9pt;
        color: #111827;
    }
    .appraisal h1 { position: absolute; top: 10mm; left: 14mm; right: 14mm; text-align: center; font-size: 13pt; margin: 0; letter-spacing: 0.5px; }

    .appraisal .info-table { position: absolute; top: 19mm; left: 14mm; right: 14mm; border-collapse: collapse; font-size: 9pt; }
    .appraisal .info-table td { padding: 0.8mm 2mm; vertical-align: top; }
    .appraisal .info-table .label { width: 26mm; }
    .appraisal .info-table .colon { width: 3mm; }
    .appraisal .info-table .sep { width: 6mm; }

    .appraisal .col-cat { position: absolute; top: 38mm; width: 121mm; }
    .appraisal .col-cat.left { left: 14mm; }
    .appraisal .col-cat.right { left: 145mm; }
    .appraisal .cat-label { font-weight: bold; font-size: 9pt; margin: 0 0 1mm; padding-left: 2mm; border-left: 1mm solid; }

    .appraisal table.grid { width: 121mm; border-collapse: collapse; }
    .appraisal table.grid th, .appraisal table.grid td { border: 0.5pt solid #cbd5e1; padding: 1mm 1.5mm; vertical-align: middle; font-size: 8pt; }
    .appraisal table.grid th {
        background-color: #042c6c;
        color: #ffffff;
        font-weight: bold;
        text-align: center;
        border: 0.5pt solid #042c6c;
        padding: 1.3mm 1.5mm;
    }
    .appraisal table.grid .col-no { width: 6mm; text-align: center; }
    .appraisal table.grid .col-grade { width: 14mm; text-align: center; }
    .appraisal table.grid td.col-grade { font-weight: bold; color: #0b47a1; font-size: 9pt; }
    .appraisal table.grid tr.alt td { background-color: #f8fafc; }
    .appraisal table.grid .criteria-title { font-weight: bold; }
    .appraisal table.grid .criteria-desc { margin-top: 0.3mm; font-size: 7.3pt; color: #374151; }
    .appraisal .cat-label.attitude { color: #1c8a4d; border-left-color: #1c8a4d; }
    .appraisal .cat-label.knowledge { color: #c74ba0; border-left-color: #c74ba0; }

    .appraisal .bottom-block { position: absolute; top: 116mm; width: 121mm; }
    .appraisal .bottom-block.left { left: 14mm; }
    .appraisal .bottom-block.right { left: 145mm; }

    .appraisal .sign-date { margin-bottom: 8mm; font-size: 9pt; }
    .appraisal .sign-line { border-top: 0.75pt solid #000; width: 50mm; padding-top: 1.5mm; font-size: 8.5pt; }

    .appraisal .summary { width: 100%; border-collapse: collapse; margin-bottom: 3mm; background-color: #eef4fc; border-radius: 1.5mm; }
    .appraisal .summary td { padding: 1mm 2mm; font-size: 8.5pt; }
    .appraisal .summary .label { width: 26mm; }
    .appraisal .summary .colon { width: 3mm; }
    .appraisal .summary .value { font-weight: bold; color: #0b47a1; }
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
        <div class="arc arc-6"></div>
        <div class="frame-outer"></div>
        <div class="frame-inner"></div>

        <div class="brand">
            @if ($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="Syifa Global Group">
            @endif
            <div class="brand-text"><b>SYIFA GLOBAL GROUP</b>INTERNSHIP</div>
        </div>

        <div class="title-block">
            <div class="eyebrow">PRAKTIK KERJA LAPANGAN</div>
            <h1>SERTIFIKAT PENGHARGAAN</h1>
            <div class="given">Dengan bangga diberikan kepada:</div>
        </div>

        <div class="name">{{ $intern->nama }}</div>

        <div class="body-text">Atas partisipasi dan dedikasinya dalam menyelesaikan program <b>Praktik Kerja Lapangan (PKL)</b> di <b>{{ $intern->unit->company->name ?? 'Syifa Global Group' }}</b>@if ($intern->tanggal_mulai && $intern->tanggal_selesai), terhitung sejak <b>{{ $intern->tanggal_mulai->translatedFormat('d F Y') }}</b> sampai dengan <b>{{ $intern->tanggal_selesai->translatedFormat('d F Y') }}</b>@endif. Semoga pengalaman ini menjadi bekal yang bermanfaat bagi pengembangan diri dan karier ke depan.</div>

        <div class="sign sign-left">
            <div class="line">
                <div class="who">{{ $intern->pembimbing->name ?? '..............................' }}</div>
                <div class="role">Pembimbing Lapangan</div>
            </div>
        </div>

        <div class="sign sign-right">
            <div class="line">
                <div class="who">..............................</div>
                <div class="role">Pimpinan Perusahaan</div>
            </div>
        </div>

        <div class="no"><span class="no-label">NO. SERTIFIKAT</span><span class="no-value">{{ $nomor }}</span></div>
    </div>

    <div class="appraisal">
        <h1>PENILAIAN</h1>

        <table class="info-table">
            <tr>
                <td class="label">Name</td><td class="colon">:</td><td>{{ $intern->nama }}</td>
                <td class="sep"></td>
                <td class="label">Department / Section</td><td class="colon">:</td><td>{{ $intern->unit->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">School</td><td class="colon">:</td><td>{{ $intern->institusi->name ?? '-' }}</td>
                <td class="sep"></td>
                <td class="label">Period</td><td class="colon">:</td>
                <td>
                    @if ($intern->tanggal_mulai && $intern->tanggal_selesai)
                        {{ $intern->tanggal_mulai->translatedFormat('d F Y') }} — {{ $intern->tanggal_selesai->translatedFormat('d F Y') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        </table>

        {{-- ATTITUDE dan KNOWLEDGE & SKILL berdampingan (bukan bertumpuk) supaya muat
             tinggi halaman 196mm tanpa perlu font mini. --}}
        @php $categoryStartNo = ['ATTITUDE' => 1, 'KNOWLEDGE & SKILL' => 6]; @endphp
        @foreach (['ATTITUDE', 'KNOWLEDGE & SKILL'] as $category)
            <div class="col-cat {{ $loop->first ? 'left' : 'right' }}">
                <p class="cat-label {{ $loop->first ? 'attitude' : 'knowledge' }}">{{ $category }}</p>
                <table class="grid">
                    <tr>
                        <th class="col-no">NO</th>
                        <th>EVALUATION CRITERIA</th>
                        <th class="col-grade">GRADE</th>
                    </tr>
                    @php $no = $categoryStartNo[$category]; @endphp
                    @foreach (\App\Models\Intern::CRITERIA as $field => $c)
                        @continue($c['category'] !== $category)
                        <tr @class(['alt' => $no % 2 === 0])>
                            <td class="col-no">{{ $no++ }}</td>
                            <td>
                                <div class="criteria-title">{{ strtoupper($c['title']) }}</div>
                                <div class="criteria-desc">{{ $c['description'] }}</div>
                            </td>
                            <td class="col-grade">{{ $intern->{$field} !== null ? number_format((float) $intern->{$field}, 2) : '-' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach

        <div class="bottom-block left">
            <div class="sign-date">Banjarbaru, {{ $tanggalTerbit }}</div>
            <div class="sign-line">{{ $intern->pembimbing->name ?? $intern->mentor->name ?? '' }}<br>Head of Department</div>
        </div>

        <div class="bottom-block right">
            @php
                $predikatColor = match ($intern->predikat()) {
                    'Excellent' => '#1c8a4d',
                    'Good' => '#0b47a1',
                    'Fair' => '#b45309',
                    'Below Average' => '#c2410c',
                    default => '#b91c1c',
                };
            @endphp
            <table class="summary">
                <tr><td class="label">Total Score</td><td class="colon">:</td><td class="value">{{ number_format($intern->nilai_akhir ?? 0, 2) }}</td></tr>
                <tr><td class="label">Rating</td><td class="colon">:</td><td class="value" style="color:{{ $predikatColor }};">{{ $intern->predikat() ?? 'Poor' }}</td></tr>
            </table>
        </div>
    </div>
</body>
</html>
