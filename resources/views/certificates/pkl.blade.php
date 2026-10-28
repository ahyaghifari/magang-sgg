@php
    use App\Support\CertificateContent;

    $company = $intern->unit->company->name ?? 'Syifa Global Group';

    // @font-face untuk font yang dipakai halaman depan — dari file TTF lokal (resources/fonts).
    $fontFaces = '';
    $variants = [
        'Regular' => ['normal', 'normal'],
        'Bold' => ['bold', 'normal'],
        'Italic' => ['normal', 'italic'],
        'BoldItalic' => ['bold', 'italic'],
    ];
    foreach (CertificateContent::usedFonts($content) as $fontKey) {
        $font = CertificateContent::FONTS[$fontKey] ?? null;
        if (! $font || ! $font['dir']) {
            continue;
        }
        foreach ($variants as $variant => $v) {
            $path = str_replace('\\', '/', resource_path("fonts/{$font['dir']}/{$variant}.ttf"));
            $fontFaces .= "@font-face { font-family: '{$font['family']}'; src: url('{$path}') format('truetype'); font-weight: {$v[0]}; font-style: {$v[1]}; }\n";
        }
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sertifikat PKL - {{ $content['nama'] }}</title>
<style>
    /*
     * Dibuat khusus untuk DomPDF (bukan browser) — TIDAK memakai flexbox/transform.
     * Ukuran halaman A4 landscape (297x210mm). Halaman depan memakai koordinat px yang SAMA
     * dengan editor sertifikat (kanvas 1123x794px pada 96 dpi) — posisi, font, ukuran, tebal,
     * miring, dan warna tiap teks diambil dari $content['styles'] (App\Support\CertificateContent).
     * Font Google didaftarkan dari file TTF lokal (resources/fonts) supaya DomPDF bisa merendernya;
     * hanya font yang benar-benar dipakai yang dimuat.
     */
    {!! $fontFaces !!}

    @page { margin: 0; size: 297mm 210mm; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans'; }

    /* Warna ditulis literal (bukan CSS var()) — DomPDF tidak konsisten meng-apply var(). */
    .cert { position: relative; width: 297mm; height: 210mm; background-color: #ffffff; overflow: hidden; }

    /* Ornamen — sama dengan editor (px). */
    .stripe { position: absolute; left: 0; right: 0; top: 0; height: 11px; background-color: #042c6c; }
    .stripe-green { position: absolute; left: 61.6%; right: 22%; top: 0; height: 11px; background-color: #1c8a4d; }
    .stripe-magenta { position: absolute; left: 78%; right: 0; top: 0; height: 11px; background-color: #c74ba0; }
    .frame-outer { position: absolute; top: 19px; left: 19px; right: 19px; bottom: 19px; border: 4px solid #042c6c; border-radius: 10px; }
    .frame-inner { position: absolute; top: 25px; left: 25px; right: 25px; bottom: 25px; border: 1.5px solid #c74ba0; border-radius: 7px; }
    .arc { position: absolute; border-radius: 50%; }
    .arc-1 { width: 550px; height: 550px; left: -275px; top: -275px; border: 53px solid #042c6c; opacity: .22; }
    .arc-2 { width: 445px; height: 445px; left: -212px; top: -212px; border: 42px solid #1c8a4d; opacity: .26; }
    .arc-3 { width: 360px; height: 360px; left: -159px; top: -159px; border: 32px solid #c74ba0; opacity: .26; }
    .arc-4 { width: 487px; height: 487px; right: -243px; bottom: -243px; border: 47px solid #042c6c; opacity: .20; }
    .arc-5 { width: 381px; height: 381px; right: -180px; bottom: -180px; border: 36px solid #1c8a4d; opacity: .24; }
    .arc-6 { width: 287px; height: 287px; right: -129px; bottom: -129px; border: 26px solid #c74ba0; opacity: .24; }

    .brand { position: absolute; top: 58px; left: 76px; width: 380px; }
    .brand img { height: 52px; }
    .brand-text { margin-top: 4px; font-size: 11px; letter-spacing: 1px; color: #64748b; font-weight: bold; }
    .brand-text b { display: block; font-size: 13px; color: #042c6c; }

    /* Elemen teks halaman depan — posisi & gaya dari inline style (CertificateContent::styleCss). */
    .el { position: absolute; }
    .el b { color: #042c6c; }
    /* Stempel di bawah, tanda tangan di atasnya (urutan elemen = urutan tumpukan di DomPDF). */
    .sig-stamp { position: absolute; top: 592px; left: 445px; width: 125px; }
    .sig-sign { position: absolute; top: 602px; left: 486.5px; width: 150px; }
    .deco-underline { border-bottom: 2px solid #c74ba0; padding-bottom: 10px; }
    .deco-signline { border-top: 2px solid #475569; padding-top: 7px; min-height: 22px; }
    .deco-pill .pill { display: inline-block; border: 1.5px solid; border-radius: 999px; padding: 6px 20px; }

    /*
     * Halaman 2 — form Appraisal. Isi & tata letaknya tetap seperti versi 280x196mm, diletakkan
     * di tengah halaman A4 (bingkai .appraisal-inner). Layout dua-kolom SENGAJA pakai posisi
     * absolut (bukan tabel dua-kolom) karena nested table di DomPDF tidak reliable.
     */
    .appraisal { position: relative; width: 297mm; height: 210mm; overflow: hidden; }
    .appraisal.after-front { page-break-before: always; }
    .appraisal.rotated { transform: rotate(180deg); transform-origin: 148.5mm 105mm; }
    .appraisal-inner { position: absolute; top: 7mm; left: 8.5mm; width: 280mm; height: 196mm; font-size: 9pt; color: #111827; }
    .appraisal-inner h1 { position: absolute; top: 10mm; left: 14mm; right: 14mm; text-align: center; font-size: 13pt; margin: 0; letter-spacing: 0.5px; }

    .appraisal-inner .info-table { position: absolute; top: 19mm; left: 14mm; right: 14mm; border-collapse: collapse; font-size: 9pt; }
    .appraisal-inner .info-table td { padding: 0.8mm 2mm; vertical-align: top; }
    .appraisal-inner .info-table .label { width: 26mm; }
    .appraisal-inner .info-table .colon { width: 3mm; }
    .appraisal-inner .info-table .sep { width: 6mm; }

    .appraisal-inner .col-cat { position: absolute; top: 46mm; width: 121mm; }
    .appraisal-inner .col-cat.left { left: 14mm; }
    .appraisal-inner .col-cat.right { left: 145mm; }
    .appraisal-inner .cat-label { font-weight: bold; font-size: 9pt; margin: 0 0 1mm; padding-left: 2mm; border-left: 1mm solid; }

    .appraisal-inner table.grid { width: 121mm; border-collapse: collapse; }
    .appraisal-inner table.grid th, .appraisal-inner table.grid td { border: 0.5pt solid #cbd5e1; padding: 1mm 1.5mm; vertical-align: middle; font-size: 8pt; }
    .appraisal-inner table.grid th { background-color: #042c6c; color: #ffffff; font-weight: bold; text-align: center; border: 0.5pt solid #042c6c; padding: 1.3mm 1.5mm; }
    .appraisal-inner table.grid .col-no { width: 6mm; text-align: center; }
    .appraisal-inner table.grid .col-grade { width: 14mm; text-align: center; }
    .appraisal-inner table.grid td.col-grade { font-weight: bold; color: #0b47a1; font-size: 9pt; }
    .appraisal-inner table.grid tr.alt td { background-color: #f8fafc; }
    .appraisal-inner table.grid .criteria-title { font-weight: bold; }
    .appraisal-inner table.grid .criteria-desc { margin-top: 0.3mm; font-size: 7.3pt; color: #374151; }
    .appraisal-inner .cat-label.attitude { color: #1c8a4d; border-left-color: #1c8a4d; }
    .appraisal-inner .cat-label.knowledge { color: #c74ba0; border-left-color: #c74ba0; }

    .appraisal-inner .bottom-block { position: absolute; top: 125mm; width: 121mm; }
    .appraisal-inner .bottom-block.left { left: 14mm; }
    .appraisal-inner .bottom-block.right { left: 145mm; }
    .appraisal-inner .sign-block { position: relative; margin-top: 4mm; text-align: center; }
    /* Stempel & tanda tangan mengisi ruang kosong antara tanggal dan garis TTD (sign-date margin-bottom 25mm). */
    .appraisal-inner .sign-stamp { position: absolute; top: 14.5mm; left: 41.5mm; width: 21mm; }
    .appraisal-inner .sign-sign { position: absolute; top: 16.5mm; left: 48mm; width: 25mm; }
    .appraisal-inner .sign-date { margin-bottom: 25mm; font-size: 9pt; text-align: center; }
    .appraisal-inner .sign-line { border-top: 0.75pt solid #000; width: 50mm; margin: 0 auto; padding-top: 1.5mm; font-size: 8.5pt; text-align: center; }
    .appraisal-inner .summary { width: 100%; border-collapse: collapse; margin-bottom: 3mm; background-color: #eef4fc; border-radius: 1.5mm; }
    .appraisal-inner .summary td { padding: 1mm 2mm; font-size: 8.5pt; }
    .appraisal-inner .summary .label { width: 26mm; }
    .appraisal-inner .summary .colon { width: 3mm; }
    .appraisal-inner .summary .value { font-weight: bold; color: #0b47a1; }
</style>
</head>
<body>
    {{-- $halaman: 'depan' | 'belakang' | null (dua-duanya). $putar: putar 180° halaman belakang (cetak manual bolak-balik). --}}
    @php
        $halaman = $halaman ?? null;
        $putar = ($putar ?? false) && $halaman === 'belakang';
    @endphp
    @if ($halaman !== 'belakang')
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

        {{-- Semua teks halaman depan: posisi & gaya dari editor sertifikat (atau bawaan). --}}
        @foreach (CertificateContent::FRONT_LAYOUT as $key => $layout)
            @php
                $style = $content['styles'][$key];
            @endphp
            <div class="el{{ isset($layout['deco']) ? ' deco-' . $layout['deco'] : '' }}" style="{{ CertificateContent::styleCss($style, true) }}">
                @if ($key === 'no')
                    No. {{ $content['no'] }}
                @elseif ($key === 'kegiatan' && ! in_array('kegiatan', $content['overridden'], true))
                    Atas partisipasi dan dedikasinya dalam menyelesaikan program <b>Praktik Kerja Lapangan (PKL)</b> di <b>{{ $company }}</b>. Semoga pengalaman ini menjadi bekal yang bermanfaat bagi pengembangan diri dan karier ke depan.
                @elseif ($key === 'kegiatan')
                    {!! nl2br(e($content['kegiatan'])) !!}
                @else
                    {{ $layout['prefix'] ?? '' }}{{ $content[$key] }}
                @endif
            </div>
        @endforeach

        @if ($signatureImages)
            <img class="sig-stamp" src="{{ $signatureImages['stamp'] }}" alt="">
            <img class="sig-sign" src="{{ $signatureImages['signature'] }}" alt="">
        @endif
    </div>
    @endif

    @if ($halaman !== 'depan')
    <div class="appraisal{{ $halaman === null ? ' after-front' : '' }}{{ $putar ? ' rotated' : '' }}">
        <div class="appraisal-inner">
            <h1>{{ $content['form_judul'] }}</h1>

            <table class="info-table">
                <tr>
                    <td class="label">Name</td><td class="colon">:</td><td>{{ $content['info_nama'] }}</td>
                    <td class="sep"></td>
                    <td class="label">Department / Section</td><td class="colon">:</td><td>{{ $content['info_unit'] }}</td>
                </tr>
                <tr>
                    <td class="label">School / University</td><td class="colon">:</td><td>{{ $content['info_sekolah'] }}</td>
                    <td class="sep"></td>
                    <td class="label">Period</td><td class="colon">:</td><td>{{ $content['info_periode'] }}</td>
                </tr>
            </table>

            {{-- ATTITUDE dan KNOWLEDGE & SKILL berdampingan. Teks kriteria & nilai dari $content
                 (bisa sudah diedit lewat editor sertifikat — nilai penilaian asli tidak berubah). --}}
            @php $categoryStartNo = ['ATTITUDE' => 1, 'KNOWLEDGE & SKILL' => 6]; @endphp
            @foreach (['ATTITUDE', 'KNOWLEDGE & SKILL'] as $category)
                <div class="col-cat {{ $loop->first ? 'left' : 'right' }}">
                    <p class="cat-label {{ $loop->first ? 'attitude' : 'knowledge' }}">{{ $content[$loop->first ? 'kategori_1' : 'kategori_2'] }}</p>
                    <table class="grid">
                        <tr>
                            <th class="col-no">NO</th>
                            <th>EVALUATION CRITERIA</th>
                            <th class="col-grade">GRADE</th>
                        </tr>
                        @php $no = $categoryStartNo[$category]; @endphp
                        @foreach (\App\Models\Intern::CRITERIA as $field => $c)
                            @continue($c['category'] !== $category)
                            @php $row = $content['kriteria'][$field]; @endphp
                            <tr @class(['alt' => $no % 2 === 0])>
                                <td class="col-no">{{ $no++ }}</td>
                                <td>
                                    <div class="criteria-title">{{ $row['nama'] }}</div>
                                    <div class="criteria-desc">{{ $row['catatan'] }}</div>
                                </td>
                                <td class="col-grade">{{ $row['nilai'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach

            {{-- Kolom kanan: kotak Total Score/Rating, lalu tepat di bawahnya blok tanda tangan (rata tengah terhadap kotak). --}}
            <div class="bottom-block right">
                <table class="summary">
                    <tr><td class="label">Total Score</td><td class="colon">:</td><td class="value">{{ $content['nilai_akhir'] }}</td></tr>
                    <tr><td class="label">Rating</td><td class="colon">:</td><td class="value" style="color:{{ CertificateContent::ratingColor($content['rating']) }};">{{ $content['rating'] }}</td></tr>
                </table>
                <div class="sign-block">
                    <div class="sign-date">{{ $content['form_tanggal'] }}</div>
                    @if ($signatureImages)
                        <img class="sign-stamp" src="{{ $signatureImages['stamp'] }}" alt="">
                        <img class="sign-sign" src="{{ $signatureImages['signature'] }}" alt="">
                    @endif
                    <div class="sign-line"><b>{{ $content['form_ttd_nama'] }}</b><br>{{ $content['form_ttd_jabatan'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</body>
</html>
