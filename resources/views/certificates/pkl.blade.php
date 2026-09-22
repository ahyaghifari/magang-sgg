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

    .predikat-box { position: absolute; top: 128mm; left: 70mm; right: 70mm; text-align: center; }
    .predikat-box .badge {
        display: inline-block; padding: 2mm 7mm; border: 1pt solid var(--green);
        border-radius: 20mm; color: var(--green); font-weight: bold; font-size: 10pt; letter-spacing: 0.5px;
    }
    .predikat-box .catatan { margin-top: 3mm; font-size: 8.5pt; font-style: italic; color: var(--slate-soft); }

    .sign { position: absolute; bottom: 14mm; width: 70mm; text-align: center; }
    .sign-left { left: 25mm; }
    .sign-right { right: 25mm; }
    .sign .line { border-top: 1pt solid #94a3b8; margin-top: 14mm; padding-top: 2mm; }
    .sign .who { font-weight: bold; color: var(--navy); font-size: 10pt; }
    .sign .role { font-size: 8pt; color: var(--slate-soft); }

    .no { position: absolute; bottom: 6mm; left: 20mm; font-size: 7pt; color: var(--slate-soft); letter-spacing: 0.5px; }

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

    .appraisal .info-table { position: absolute; top: 22mm; left: 14mm; right: 14mm; border-collapse: collapse; font-size: 9pt; }
    .appraisal .info-table td { padding: 0.8mm 2mm; vertical-align: top; }
    .appraisal .info-table .label { width: 26mm; }
    .appraisal .info-table .colon { width: 3mm; }
    .appraisal .info-table .sep { width: 6mm; }

    .appraisal .col-cat { position: absolute; top: 38mm; width: 121mm; }
    .appraisal .col-cat.left { left: 14mm; }
    .appraisal .col-cat.right { left: 145mm; }
    .appraisal .cat-label { font-weight: bold; font-size: 9pt; margin: 0 0 1mm; }

    .appraisal table.grid { width: 121mm; border-collapse: collapse; }
    .appraisal table.grid th, .appraisal table.grid td { border: 0.75pt solid #000; padding: 0.8mm 1.5mm; vertical-align: top; font-size: 8pt; }
    .appraisal table.grid th { background: #e5e7eb; font-weight: bold; text-align: center; }
    .appraisal table.grid .col-no { width: 6mm; text-align: center; }
    .appraisal table.grid .col-grade { width: 14mm; text-align: center; }
    .appraisal table.grid .criteria-title { font-weight: bold; }
    .appraisal table.grid .criteria-desc { margin-top: 0.3mm; font-size: 7.3pt; color: #374151; }

    .appraisal .bottom-block { position: absolute; top: 116mm; width: 121mm; }
    .appraisal .bottom-block.left { left: 14mm; }
    .appraisal .bottom-block.right { left: 145mm; }

    .appraisal .sign-date { margin-bottom: 8mm; font-size: 9pt; }
    .appraisal .sign-line { border-top: 0.75pt solid #000; width: 50mm; padding-top: 1.5mm; font-size: 8.5pt; }

    .appraisal .summary { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
    .appraisal .summary td { padding: 0.6mm 1mm; font-size: 8.5pt; }
    .appraisal .summary .label { width: 26mm; }
    .appraisal .summary .colon { width: 3mm; }
    .appraisal .summary .value { font-weight: bold; }

    .appraisal .legend { font-size: 7.5pt; }
    .appraisal .legend table { border-collapse: collapse; margin-top: 1mm; }
    .appraisal .legend td { padding: 0.3mm 3mm 0.3mm 0; }
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

        @if ($intern->nilai_akhir !== null)
            <div class="predikat-box">
                <span class="badge">NILAI AKHIR: {{ number_format($intern->nilai_akhir, 2) }} — {{ strtoupper($intern->predikat()) }}</span>
                @if ($intern->catatan_penilaian)
                    <div class="catatan">&ldquo;{{ $intern->catatan_penilaian }}&rdquo;</div>
                @endif
            </div>
        @endif

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

    <div class="appraisal">
        <h1>APPRAISAL ON THE JOB TRAINING RESULT</h1>

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
                <p class="cat-label">{{ $category }}</p>
                <table class="grid">
                    <tr>
                        <th class="col-no">NO</th>
                        <th>EVALUATION CRITERIA</th>
                        <th class="col-grade">GRADE</th>
                    </tr>
                    @php $no = $categoryStartNo[$category]; @endphp
                    @foreach (\App\Models\Intern::CRITERIA as $field => $c)
                        @continue($c['category'] !== $category)
                        <tr>
                            <td class="col-no">{{ $no++ }}</td>
                            <td>
                                <div class="criteria-title">{{ strtoupper($c['title']) }}</div>
                                <div class="criteria-desc">{{ $c['description'] }}</div>
                            </td>
                            <td class="col-grade">{{ number_format($intern->{$field} ?? 0, 2) }}</td>
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
            <table class="summary">
                <tr><td class="label">Total Score</td><td class="colon">:</td><td class="value">{{ number_format($intern->nilai_akhir ?? 0, 2) }}</td></tr>
                <tr><td class="label">Grade</td><td class="colon">:</td><td class="value">{{ number_format($intern->nilai_akhir ?? 0, 2) }}</td></tr>
                <tr><td class="label">Rating</td><td class="colon">:</td><td class="value">{{ $intern->predikat() ?? 'Poor' }}</td></tr>
            </table>
            <div class="legend">
                Scoring 1 - 4, with rating scale :
                <table>
                    <tr><td>3.50 - 4.00</td><td>: Excellent</td></tr>
                    <tr><td>3.00 - 3.49</td><td>: Good</td></tr>
                    <tr><td>2.50 - 2.99</td><td>: Fair</td></tr>
                    <tr><td>1.50 - 2.49</td><td>: Below Average</td></tr>
                    <tr><td>0.00 - 1.49</td><td>: Poor</td></tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
