@php
    use App\Models\Intern;
    use App\Support\CertificateContent;

    // Semua isi diambil dari $content = data asli digabung editan tersimpan
    // (App\Support\CertificateContent::for).
    //
    // Halaman BELAKANG: $ed(key) → atribut data-key (+ contenteditable kalau boleh mengedit).
    // Halaman DEPAN: tiap elemen [data-front] punya posisi & gaya sendiri ($content['styles']),
    // bisa dipilih (klik), digeser (drag), diatur gayanya (panel), dan diketik (klik ganda /
    // tombol "Edit teks"). Posisi & gaya yang sama dipakai PDF DomPDF (certificates/pkl.blade.php).
    $ed = fn (string $key) => 'data-key="' . e($key) . '"' . ($canEdit ? ' contenteditable="true" spellcheck="false"' : '');

    $isOverridden = fn (string $key) => in_array($key, $content['overridden'], true);
    $company = $intern->unit->company->name ?? 'Syifa Global Group';
    $meta = $content['meta'];

    $front = function (string $key) use ($content) {
        $style = $content['styles'][$key];
        $default = CertificateContent::defaultStyle($key, $content);
        $pick = fn ($s) => collect($s)->only(CertificateContent::STYLE_PROPS)->all();

        return 'data-key="' . e($key) . '" data-front'
            . ' data-style="' . e(json_encode($pick($style))) . '"'
            . ' data-default="' . e(json_encode($pick($default))) . '"'
            . ' data-width="' . $style['width'] . '"'
            . ' style="' . e(CertificateContent::styleCss($style)) . '"';
    };
    $decoClass = fn (string $key) => 'el' . (isset(CertificateContent::FRONT_LAYOUT[$key]['deco']) ? ' deco-' . CertificateContent::FRONT_LAYOUT[$key]['deco'] : '');
    $prefix = fn (string $key) => CertificateContent::FRONT_LAYOUT[$key]['prefix'] ?? null;

    // Nama elemen yang tampil di panel gaya.
    $labels = [
        'judul' => 'Judul', 'pengantar' => 'Kalimat pengantar', 'nama' => 'Nama peserta',
        'sekolah' => 'Asal sekolah', 'kegiatan' => 'Kalimat kegiatan', 'periode' => 'Periode',
        'predikat' => 'Predikat', 'tanggal' => 'Tempat & tanggal', 'ttd_nama' => 'Nama TTD kiri',
        'ttd_jabatan' => 'Jabatan TTD kiri', 'ttd2_nama' => 'Nama TTD kanan', 'ttd2_jabatan' => 'Jabatan TTD kanan',
        'no' => 'Nomor sertifikat',
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Editor Sertifikat</title>
<link rel="icon" href="{{ \App\Support\Brand::faviconUrl() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Semua font yang bisa dipilih di panel gaya (daftar sama dengan CertificateContent::FONTS). --}}
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,700;0,800;1,400;1,700&family=Poppins:ital,wght@0,400;0,700;1,400;1,700&family=Roboto:ital,wght@0,400;0,700;1,400;1,700&family=Montserrat:ital,wght@0,400;0,700;1,400;1,700&family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Merriweather:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
<style>
    /*
     * Editor sertifikat — murni untuk browser (bukan DomPDF), jadi bebas pakai flex dsb.
     * Kanvas tiap halaman 1123x794 px (= A4 landscape pada 96 dpi). Elemen halaman depan
     * diposisikan absolut dengan top/left px — koordinat yang sama dipakai PDF DomPDF.
     * Toolbar, panel gaya, garis bantu, dan sorotan tidak ikut ke PDF (.exporting) maupun print.
     */
    :root {
        --navy: #042c6c;
        --blue: #0b47a1;
        --green: #1c8a4d;
        --magenta: #c74ba0;
        --muted: #64748b;
        --body: #334155;
        --app-bg: #e2e8f0;
        --bar-bg: #ffffff;
        --bar-text: #0f172a;
        --bar-muted: #64748b;
        --bar-border: #cbd5e1;
        --field-bg: #ffffff;
    }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) {
            --app-bg: #0b1220;
            --bar-bg: #0f172a;
            --bar-text: #e2e8f0;
            --bar-muted: #94a3b8;
            --bar-border: #1e293b;
            --field-bg: #111c33;
        }
    }
    :root[data-theme="dark"] {
        --app-bg: #0b1220;
        --bar-bg: #0f172a;
        --bar-text: #e2e8f0;
        --bar-muted: #94a3b8;
        --bar-border: #1e293b;
        --field-bg: #111c33;
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { background: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #111827; }

    /* ===== Toolbar + panel gaya (tidak ikut PDF/print) ===== */
    .topbar { position: sticky; top: 0; z-index: 20; background: var(--bar-bg); border-bottom: 1px solid var(--bar-border); box-shadow: 0 2px 10px rgba(2, 6, 23, 0.06); }
    .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 10px 16px; }
    .toolbar-title { font-weight: 700; font-size: 15px; color: var(--bar-text); }
    .toolbar-hint { font-size: 13px; color: var(--bar-muted); margin-top: 2px; }
    .toolbar-meta { font-size: 12px; color: var(--bar-muted); margin-top: 3px; }
    .toolbar-meta b { color: var(--bar-text); }
    .toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .btn-save { background: var(--green); border-color: var(--green); color: #fff; }
    .btn-save:hover { background: #167240; border-color: #167240; }
    .save-status { font-size: 13px; font-weight: 600; min-width: 1px; }
    .save-status.ok { color: var(--green); }
    .save-status.err { color: #dc2626; }
    .save-status.dirty { color: #b45309; }
    .btn {
        font: inherit; font-size: 14px; font-weight: 600; cursor: pointer;
        padding: 8px 14px; border-radius: 10px; border: 1px solid var(--bar-border);
        background: transparent; color: var(--bar-text);
    }
    .btn:hover { border-color: var(--blue); }
    .btn-primary { background: var(--navy); border-color: var(--navy); color: #fff; }
    .btn-primary:hover { background: var(--blue); border-color: var(--blue); }
    .btn[disabled] { opacity: 0.6; cursor: progress; }
    .btn-sm { padding: 6px 10px; font-size: 13px; border-radius: 8px; }
    .btn-toggle[aria-pressed="true"] { background: var(--navy); border-color: var(--navy); color: #fff; }

    .stylebar { display: none; align-items: center; gap: 8px; flex-wrap: wrap; padding: 8px 16px 10px; border-top: 1px dashed var(--bar-border); }
    .stylebar.show { display: flex; }
    .stylebar-label { font-size: 13px; font-weight: 700; color: var(--bar-text); margin-right: 4px; }
    .stylebar-label span { font-weight: 500; color: var(--bar-muted); }
    .field { font: inherit; font-size: 13px; padding: 6px 8px; border-radius: 8px; border: 1px solid var(--bar-border); background: var(--field-bg); color: var(--bar-text); }
    .size-group { display: inline-flex; align-items: center; gap: 4px; }
    .size-group .field { width: 58px; text-align: center; }
    .color-field { width: 38px; height: 32px; padding: 2px; border-radius: 8px; border: 1px solid var(--bar-border); background: var(--field-bg); cursor: pointer; }
    .stylebar-sep { width: 1px; height: 24px; background: var(--bar-border); }

    /* ===== Area halaman ===== */
    .pages { padding: 24px 16px 48px; overflow-x: auto; }
    .page {
        position: relative; width: 1123px; height: 794px; margin: 0 auto 24px;
        background: #ffffff; overflow: hidden; box-shadow: 0 8px 30px rgba(2, 6, 23, 0.18);
    }

    /* Sorotan teks yang bisa diedit (halaman belakang) — hanya di layar. */
    [contenteditable="true"] { outline: none; border-radius: 3px; transition: background-color .12s, box-shadow .12s; cursor: text; }
    [contenteditable="true"]:hover { box-shadow: 0 0 0 1px rgba(11, 71, 161, 0.45); }
    [contenteditable="true"]:focus { box-shadow: 0 0 0 2px #0b47a1; background-color: rgba(11, 71, 161, 0.06); }
    [contenteditable="true"]:empty::before { content: attr(data-placeholder); color: #94a3b8; font-style: italic; }

    /* ===== Halaman 1: Sertifikat — ornamen (sama dengan PDF) ===== */
    .stripe { position: absolute; left: 0; right: 0; top: 0; height: 11px; background: var(--navy); }
    .stripe-green { position: absolute; left: 61.6%; right: 22%; top: 0; height: 11px; background: var(--green); }
    .stripe-magenta { position: absolute; left: 78%; right: 0; top: 0; height: 11px; background: var(--magenta); }
    .frame-outer { position: absolute; top: 19px; left: 19px; right: 19px; bottom: 19px; border: 4px solid var(--navy); border-radius: 10px; }
    .frame-inner { position: absolute; top: 25px; left: 25px; right: 25px; bottom: 25px; border: 1.5px solid var(--magenta); border-radius: 7px; }
    .arc { position: absolute; border-radius: 50%; }
    .arc-1 { width: 550px; height: 550px; left: -275px; top: -275px; border: 53px solid var(--navy); opacity: .22; }
    .arc-2 { width: 445px; height: 445px; left: -212px; top: -212px; border: 42px solid var(--green); opacity: .26; }
    .arc-3 { width: 360px; height: 360px; left: -159px; top: -159px; border: 32px solid var(--magenta); opacity: .26; }
    .arc-4 { width: 487px; height: 487px; right: -243px; bottom: -243px; border: 47px solid var(--navy); opacity: .20; }
    .arc-5 { width: 381px; height: 381px; right: -180px; bottom: -180px; border: 36px solid var(--green); opacity: .24; }
    .arc-6 { width: 287px; height: 287px; right: -129px; bottom: -129px; border: 26px solid var(--magenta); opacity: .24; }

    .brand { position: absolute; top: 58px; left: 76px; }
    .brand img { height: 46px; display: block; }
    .brand-text { margin-top: 4px; font-size: 11px; letter-spacing: 1px; color: var(--muted); font-weight: 700; }
    .brand-text b { display: block; font-size: 13px; color: var(--navy); }

    /* ===== Elemen teks halaman depan (posisi & gaya dari inline style) ===== */
    .el { position: absolute; white-space: pre-line; border-radius: 3px; }
    .el b { color: var(--navy); }
    .deco-underline { border-bottom: 2px solid var(--magenta); padding-bottom: 10px; }
    .deco-signline { border-top: 1.3px solid #94a3b8; padding-top: 7px; }
    .deco-pill .pill { display: inline-block; border: 1.5px solid currentColor; border-radius: 999px; padding: 6px 20px; }
    .el .prefix-no { color: var(--muted); letter-spacing: 1.2px; margin-right: 8px; }

    .can-edit .el { cursor: grab; }
    .can-edit .el:hover { box-shadow: 0 0 0 1px rgba(11, 71, 161, 0.35); }
    .can-edit .el.selected { outline: 1.5px dashed #0b47a1; outline-offset: 3px; box-shadow: none; touch-action: none; }
    .can-edit .el.dragging { cursor: grabbing; opacity: .9; }
    .can-edit .el.editing { cursor: text; outline: 2px solid #0b47a1; outline-offset: 3px; background: rgba(11, 71, 161, 0.05); }
    .el .txt[contenteditable="true"] { box-shadow: none; background: transparent; }

    /* Garis bantu tengah halaman saat elemen sejajar tengah */
    .guide-v { position: absolute; top: 0; bottom: 0; left: 561.5px; width: 0; border-left: 1px dashed #c74ba0; display: none; pointer-events: none; z-index: 5; }
    .guide-v.show { display: block; }

    .exporting .el, .exporting [contenteditable="true"] { box-shadow: none !important; outline: none !important; background-color: transparent !important; }
    .exporting [contenteditable="true"]:empty::before { content: none; }
    .exporting .guide-v { display: none !important; }

    /* ===== Halaman 2: Form penilaian ===== */
    .b-title { position: absolute; top: 34px; left: 53px; right: 53px; text-align: center; font-size: 19px; font-weight: 800; letter-spacing: .6px; color: #111827; }
    .b-subtitle { position: absolute; top: 62px; left: 53px; right: 53px; text-align: center; font-size: 12px; color: var(--muted); }
    .b-info { position: absolute; top: 92px; left: 53px; right: 53px; border-collapse: collapse; font-size: 13px; }
    .b-info td { padding: 3px 6px; vertical-align: top; }
    .b-info .label { width: 150px; color: var(--muted); }
    .b-info .colon { width: 12px; }
    .b-info .sep { width: 24px; }
    .b-info .val { font-weight: 600; }

    .b-col { position: absolute; top: 160px; width: 492px; }
    .b-col.left { left: 53px; }
    .b-col.right { left: 578px; }
    .b-cat { font-weight: 800; font-size: 12.5px; margin: 0 0 5px; padding-left: 8px; border-left: 4px solid; }
    .b-cat.attitude { color: var(--green); border-left-color: var(--green); }
    .b-cat.knowledge { color: var(--magenta); border-left-color: var(--magenta); }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th, table.grid td { border: 1px solid #cbd5e1; padding: 5px 7px; vertical-align: middle; font-size: 11px; }
    table.grid th { background: var(--navy); color: #fff; font-weight: 700; text-align: center; border-color: var(--navy); padding: 6px 7px; }
    table.grid .col-no { width: 32px; text-align: center; }
    table.grid .col-grade { width: 64px; text-align: center; }
    table.grid td.col-grade { font-weight: 800; color: var(--blue); font-size: 13px; }
    table.grid tr.alt td { background: #f8fafc; }
    .c-title { font-weight: 700; }
    .c-desc { margin-top: 1px; font-size: 10px; color: #374151; }

    .b-notes { position: absolute; top: 480px; left: 53px; width: 492px; }
    .b-notes-label { font-size: 12px; font-weight: 800; color: var(--navy); margin-bottom: 5px; letter-spacing: .4px; }
    .b-notes-box { min-height: 128px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px 11px; font-size: 12px; line-height: 1.6; color: var(--body); white-space: pre-wrap; }

    .b-summary { position: absolute; top: 480px; left: 578px; width: 492px; border-collapse: collapse; background: #eef4fc; border-radius: 8px; overflow: hidden; }
    .b-summary td { padding: 6px 10px; font-size: 13px; }
    .b-summary .label { width: 130px; }
    .b-summary .colon { width: 12px; }
    .b-summary .value { font-weight: 800; color: var(--blue); }

    .b-sign { position: absolute; top: 580px; left: 578px; width: 492px; }
    .b-sign-date { font-size: 12.5px; margin-bottom: 52px; }
    .b-sign-line { border-top: 1px solid #000; width: 230px; padding-top: 6px; font-size: 12.5px; }
    .b-sign-line .who { font-weight: 700; }

    /* ===== Print ===== */
    @page { size: A4 landscape; margin: 0; }
    @media print {
        html, body { background: #ffffff; }
        .topbar, .guide-v { display: none !important; }
        .pages { padding: 0; overflow: visible; }
        .page { margin: 0; box-shadow: none; page-break-after: always; break-after: page; }
        .page:last-child { page-break-after: auto; break-after: auto; }
        .el, [contenteditable="true"] { box-shadow: none !important; outline: none !important; background: transparent !important; }
        [contenteditable="true"]:empty::before { content: none; }
    }
</style>
</head>
<body class="{{ $canEdit ? 'can-edit' : '' }}">
    <div class="topbar">
        <div class="toolbar">
            <div>
                <div class="toolbar-title">Editor Sertifikat &middot; {{ $intern->nama }}</div>
                <div class="toolbar-hint">
                    @if ($canEdit)
                        Halaman depan: <b>klik</b> teks untuk memilih lalu geser/atur gaya, <b>klik ganda</b> untuk mengetik.
                        Tekan <b>Simpan</b> (Ctrl+S). Mengubah angka di sertifikat <b>tidak</b> mengubah data penilaian aslinya.
                    @else
                        Versi final (baca-saja).
                    @endif
                </div>
                <div class="toolbar-meta" id="edit-meta"
                     data-empty="Belum pernah diedit — menampilkan data asli.">
                    @if ($meta['updated_at'])
                        Terakhir diedit oleh <b>{{ $meta['updated_by'] ?? 'pengguna dihapus' }}</b> &middot; {{ $meta['updated_at']->translatedFormat('d M Y, H:i') }}
                    @else
                        Belum pernah diedit — menampilkan data asli.
                    @endif
                </div>
            </div>
            <div class="toolbar-actions">
                @if ($canEdit)
                    <span class="save-status" id="save-status" aria-live="polite"></span>
                    <button type="button" class="btn" id="btn-reset">Reset ke Data Asli</button>
                    <button type="button" class="btn btn-save" id="btn-save">Simpan</button>
                @endif
                <button type="button" class="btn" id="btn-print">Print</button>
                <button type="button" class="btn btn-primary" id="btn-pdf">Unduh PDF</button>
            </div>
        </div>

        @if ($canEdit)
            {{-- Panel gaya: muncul saat satu elemen halaman depan dipilih. --}}
            <div class="stylebar" id="stylebar" role="toolbar" aria-label="Gaya teks">
                <span class="stylebar-label">Elemen: <span id="sb-name">-</span></span>
                <select class="field" id="sb-font" aria-label="Font">
                    @foreach (CertificateContent::FONTS as $fontKey => $font)
                        <option value="{{ $fontKey }}" style="font-family: '{{ $font['family'] }}', {{ $font['fallback'] }};">{{ $font['label'] }}</option>
                    @endforeach
                </select>
                <span class="size-group">
                    <button type="button" class="btn btn-sm" id="sb-size-down" aria-label="Perkecil">−</button>
                    <input type="number" class="field" id="sb-size" min="{{ CertificateContent::SIZE_MIN }}" max="{{ CertificateContent::SIZE_MAX }}" step="0.5" aria-label="Ukuran (px)">
                    <button type="button" class="btn btn-sm" id="sb-size-up" aria-label="Perbesar">+</button>
                </span>
                <button type="button" class="btn btn-sm btn-toggle" id="sb-bold" aria-pressed="false" title="Tebal"><b>B</b></button>
                <button type="button" class="btn btn-sm btn-toggle" id="sb-italic" aria-pressed="false" title="Miring"><i>I</i></button>
                <input type="color" class="color-field" id="sb-color" aria-label="Warna teks">
                <span class="stylebar-sep"></span>
                <button type="button" class="btn btn-sm" id="sb-edit">Edit teks</button>
                <button type="button" class="btn btn-sm" id="sb-reset">Reset gaya elemen ini</button>
                <button type="button" class="btn btn-sm" id="sb-done">Selesai</button>
            </div>
        @endif
    </div>

    <div class="pages" id="pages">
        {{-- ===== Halaman 1: Sertifikat PKL ===== --}}
        <section class="page" id="page-front">
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
            <div class="guide-v" id="guide-v"></div>

            <div class="brand">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="Syifa Global Group">
                @endif
                <div class="brand-text"><b>SYIFA GLOBAL GROUP</b>INTERNSHIP</div>
            </div>

            @foreach (array_keys(CertificateContent::FRONT_LAYOUT) as $key)
                <div class="{{ $decoClass($key) }}" {!! $front($key) !!} data-label="{{ $labels[$key] ?? $key }}">
                    @if ($key === 'predikat')
                        <span class="pill">{{ $prefix($key) }}<span class="txt" id="front-predikat-value">{{ $content['predikat'] }}</span></span>
                    @elseif ($key === 'no')
                        <span class="prefix-no">NO. SERTIFIKAT</span><span class="txt">{{ $content['no'] }}</span>
                    @elseif ($key === 'kegiatan' && ! $isOverridden('kegiatan'))
                        {{-- Belum diedit: tampil dengan penebalan (teks polosnya identik dengan data asli). --}}
                        <span class="txt">Atas partisipasi dan dedikasinya dalam menyelesaikan program <b>Praktik Kerja Lapangan (PKL)</b> di <b>{{ $company }}</b>. Semoga pengalaman ini menjadi bekal yang bermanfaat bagi pengembangan diri dan karier ke depan.</span>
                    @else
                        {{ $prefix($key) }}<span class="txt">{{ $content[$key] }}</span>
                    @endif
                </div>
            @endforeach
        </section>

        {{-- ===== Halaman 2: Form penilaian (10 kriteria) ===== --}}
        <section class="page" id="page-back">
            <div class="b-title" {!! $ed('form_judul') !!}>{{ $content['form_judul'] }}</div>
            <div class="b-subtitle" {!! $ed('form_subjudul') !!}>{{ $content['form_subjudul'] }}</div>

            <table class="b-info">
                <tr>
                    <td class="label">Name</td><td class="colon">:</td><td class="val" {!! $ed('info_nama') !!}>{{ $content['info_nama'] }}</td>
                    <td class="sep"></td>
                    <td class="label">Department / Section</td><td class="colon">:</td><td class="val" {!! $ed('info_unit') !!}>{{ $content['info_unit'] }}</td>
                </tr>
                <tr>
                    <td class="label">School</td><td class="colon">:</td><td class="val" {!! $ed('info_sekolah') !!}>{{ $content['info_sekolah'] }}</td>
                    <td class="sep"></td>
                    <td class="label">Period</td><td class="colon">:</td><td class="val" {!! $ed('info_periode') !!}>{{ $content['info_periode'] }}</td>
                </tr>
            </table>

            @php $startNo = ['ATTITUDE' => 1, 'KNOWLEDGE & SKILL' => 6]; @endphp
            @foreach (['ATTITUDE', 'KNOWLEDGE & SKILL'] as $category)
                <div class="b-col {{ $loop->first ? 'left' : 'right' }}">
                    <p class="b-cat {{ $loop->first ? 'attitude' : 'knowledge' }}" {!! $ed($loop->first ? 'kategori_1' : 'kategori_2') !!}>{{ $content[$loop->first ? 'kategori_1' : 'kategori_2'] }}</p>
                    <table class="grid">
                        <tr>
                            <th class="col-no">NO</th>
                            <th>EVALUATION CRITERIA</th>
                            <th class="col-grade">GRADE</th>
                        </tr>
                        @php $no = $startNo[$category]; @endphp
                        @foreach (Intern::CRITERIA as $field => $c)
                            @continue($c['category'] !== $category)
                            @php $row = $content['kriteria'][$field]; @endphp
                            <tr @class(['alt' => $no % 2 === 0])>
                                <td class="col-no">{{ $no++ }}</td>
                                <td>
                                    <div class="c-title" {!! $ed("kriteria.{$field}.nama") !!}>{{ $row['nama'] }}</div>
                                    <div class="c-desc" {!! $ed("kriteria.{$field}.catatan") !!}>{{ $row['catatan'] }}</div>
                                </td>
                                <td class="col-grade" data-grade {!! $ed("kriteria.{$field}.nilai") !!}>{{ $row['nilai'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach

            <div class="b-notes">
                <div class="b-notes-label">CATATAN PENILAI</div>
                <div class="b-notes-box" data-placeholder="Tulis catatan penilaian di sini…" {!! $ed('catatan_penilai') !!}>{{ $content['catatan_penilai'] !== '' ? $content['catatan_penilai'] : ($canEdit ? '' : '-') }}</div>
            </div>

            <table class="b-summary">
                <tr><td class="label">Total Score</td><td class="colon">:</td><td class="value" id="total-score" {!! $ed('nilai_akhir') !!}>{{ $content['nilai_akhir'] }}</td></tr>
                <tr><td class="label">Rating</td><td class="colon">:</td><td class="value" id="rating" style="color: {{ CertificateContent::ratingColor($content['rating']) }};" {!! $ed('rating') !!}>{{ $content['rating'] }}</td></tr>
            </table>

            <div class="b-sign">
                <div class="b-sign-date" {!! $ed('form_tanggal') !!}>{{ $content['form_tanggal'] }}</div>
                <div class="b-sign-line">
                    <div class="who" {!! $ed('form_ttd_nama') !!}>{{ $content['form_ttd_nama'] }}</div>
                    <div {!! $ed('form_ttd_jabatan') !!}>{{ $content['form_ttd_jabatan'] }}</div>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        (function () {
            const filename = @js($filename);
            const canEdit = @js($canEdit);
            const pages = Array.from(document.querySelectorAll('.page'));
            const PAGE_W = {{ CertificateContent::PAGE_WIDTH }};
            const PAGE_H = {{ CertificateContent::PAGE_HEIGHT }};
            const FONTS = @js(collect(CertificateContent::FONTS)->map(fn ($f) => "'{$f['family']}', {$f['fallback']}"));
            const SIZE_MIN = {{ CertificateContent::SIZE_MIN }};
            const SIZE_MAX = {{ CertificateContent::SIZE_MAX }};

            // Tempel selalu sebagai teks polos, supaya format dari Word/WhatsApp tidak ikut masuk.
            document.addEventListener('paste', function (e) {
                if (!e.target.closest || !e.target.closest('[contenteditable="true"]')) return;
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertText', false, text);
            });

            // ===== Status elemen halaman depan (posisi & gaya) =====
            const frontEls = Array.from(document.querySelectorAll('[data-front]'));
            const state = new Map();
            frontEls.forEach(function (el) {
                state.set(el, {
                    style: JSON.parse(el.dataset.style),
                    def: JSON.parse(el.dataset.default),
                    width: parseFloat(el.dataset.width),
                });
            });

            function applyStyle(el) {
                const s = state.get(el).style;
                el.style.top = s.top + 'px';
                el.style.left = s.left + 'px';
                el.style.fontFamily = FONTS[s.font] || FONTS.jakarta;
                el.style.fontSize = s.size + 'px';
                el.style.fontWeight = s.bold ? 'bold' : 'normal';
                el.style.fontStyle = s.italic ? 'italic' : 'normal';
                el.style.color = s.color;
            }

            // ===== Nilai per kriteria → hitung ulang Total Score, Rating & Predikat depan =====
            const colors = { 'Excellent': '#1c8a4d', 'Good': '#0b47a1', 'Fair': '#b45309', 'Below Average': '#c2410c', 'Poor': '#b91c1c' };
            function ratingFor(v) {
                if (v >= 4.5) return 'Excellent';
                if (v >= 3.5) return 'Good';
                if (v >= 2.5) return 'Fair';
                if (v >= 1.5) return 'Below Average';
                return 'Poor';
            }
            document.querySelectorAll('[data-grade]').forEach(function (cell) {
                cell.addEventListener('input', function () {
                    const values = Array.from(document.querySelectorAll('[data-grade]'))
                        .map(function (c) { return parseFloat(c.textContent.trim().replace(',', '.')); });
                    if (values.some(function (v) { return isNaN(v); })) return;
                    const avg = values.reduce(function (a, b) { return a + b; }, 0) / values.length;
                    const rating = ratingFor(avg);
                    document.getElementById('total-score').textContent = avg.toFixed(2);
                    const ratingEl = document.getElementById('rating');
                    ratingEl.textContent = rating;
                    ratingEl.style.color = colors[rating];
                    document.getElementById('front-predikat-value').textContent = rating;
                    // Warna predikat depan ikut rating, kecuali warnanya sudah diubah manual.
                    const predikatEl = document.querySelector('[data-front][data-key="predikat"]');
                    const st = state.get(predikatEl);
                    const auto = st.style.color.toLowerCase() === st.def.color.toLowerCase();
                    st.def.color = colors[rating];
                    if (auto) { st.style.color = colors[rating]; applyStyle(predikatEl); }
                });
            });

            // ===== Unduh PDF (browser) & Print =====
            const btnPdf = document.getElementById('btn-pdf');
            btnPdf.addEventListener('click', async function () {
                const label = btnPdf.textContent;
                btnPdf.disabled = true;
                btnPdf.textContent = 'Membuat PDF…';
                if (window.deselect) window.deselect();
                document.body.classList.add('exporting');
                if (document.activeElement) document.activeElement.blur();

                try {
                    if (document.fonts && document.fonts.ready) await document.fonts.ready;
                    const pdf = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
                    for (let i = 0; i < pages.length; i++) {
                        const canvas = await html2canvas(pages[i], {
                            scale: 2,
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            width: PAGE_W,
                            height: PAGE_H,
                            windowWidth: PAGE_W,
                        });
                        if (i > 0) pdf.addPage('a4', 'landscape');
                        pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, 297, 210);
                    }
                    pdf.save(filename);
                } catch (err) {
                    console.error(err);
                    alert('Gagal membuat PDF: ' + (err && err.message ? err.message : err));
                } finally {
                    document.body.classList.remove('exporting');
                    btnPdf.disabled = false;
                    btnPdf.textContent = label;
                }
            });

            document.getElementById('btn-print').addEventListener('click', function () {
                if (window.deselect) window.deselect();
                if (document.activeElement) document.activeElement.blur();
                window.print();
            });

            if (!canEdit) return;

            // ===== Pilih / geser / ketik elemen halaman depan =====
            const stylebar = document.getElementById('stylebar');
            const sb = {
                name: document.getElementById('sb-name'),
                font: document.getElementById('sb-font'),
                size: document.getElementById('sb-size'),
                sizeDown: document.getElementById('sb-size-down'),
                sizeUp: document.getElementById('sb-size-up'),
                bold: document.getElementById('sb-bold'),
                italic: document.getElementById('sb-italic'),
                color: document.getElementById('sb-color'),
                edit: document.getElementById('sb-edit'),
                reset: document.getElementById('sb-reset'),
                done: document.getElementById('sb-done'),
            };
            const guide = document.getElementById('guide-v');
            let selected = null;
            let editing = null;
            let dirty = false;

            function markDirty() {
                dirty = true;
                setStatus('Belum disimpan', 'dirty');
            }

            function syncPanel() {
                if (!selected) return;
                const s = state.get(selected).style;
                sb.name.textContent = selected.dataset.label;
                sb.font.value = s.font;
                sb.size.value = s.size;
                sb.bold.setAttribute('aria-pressed', s.bold ? 'true' : 'false');
                sb.italic.setAttribute('aria-pressed', s.italic ? 'true' : 'false');
                sb.color.value = s.color;
            }

            function select(el) {
                if (selected === el) return;
                if (editing) exitEdit();
                if (selected) selected.classList.remove('selected');
                selected = el;
                el.classList.add('selected');
                stylebar.classList.add('show');
                syncPanel();
            }

            window.deselect = function () {
                if (editing) exitEdit();
                if (selected) selected.classList.remove('selected');
                selected = null;
                stylebar.classList.remove('show');
                guide.classList.remove('show');
            };
            const deselect = window.deselect;

            function enterEdit(el) {
                select(el);
                const txt = el.querySelector('.txt');
                if (!txt) return;
                editing = el;
                el.classList.add('editing');
                txt.setAttribute('contenteditable', 'true');
                txt.setAttribute('spellcheck', 'false');
                txt.focus();
                // Kursor di akhir teks.
                const range = document.createRange();
                range.selectNodeContents(txt);
                range.collapse(false);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }

            function exitEdit() {
                if (!editing) return;
                const txt = editing.querySelector('.txt');
                txt.removeAttribute('contenteditable');
                editing.classList.remove('editing');
                editing = null;
            }

            function update(prop, value) {
                if (!selected) return;
                state.get(selected).style[prop] = value;
                applyStyle(selected);
                syncPanel();
                markDirty();
            }

            // Panel gaya
            sb.font.addEventListener('change', function () { update('font', sb.font.value); });
            function setSize(v) {
                v = Math.round(Math.max(SIZE_MIN, Math.min(SIZE_MAX, v)) * 2) / 2;
                if (!isNaN(v)) update('size', v);
            }
            sb.size.addEventListener('change', function () { setSize(parseFloat(sb.size.value)); });
            sb.sizeDown.addEventListener('click', function () { setSize(state.get(selected).style.size - 1); });
            sb.sizeUp.addEventListener('click', function () { setSize(state.get(selected).style.size + 1); });
            sb.bold.addEventListener('click', function () { update('bold', !state.get(selected).style.bold); });
            sb.italic.addEventListener('click', function () { update('italic', !state.get(selected).style.italic); });
            sb.color.addEventListener('input', function () { update('color', sb.color.value); });
            sb.edit.addEventListener('click', function () { if (selected) enterEdit(selected); });
            sb.done.addEventListener('click', deselect);
            sb.reset.addEventListener('click', function () {
                if (!selected) return;
                const st = state.get(selected);
                st.style = Object.assign({}, st.def);
                applyStyle(selected);
                syncPanel();
                markDirty();
            });

            // Klik sekali = pilih + bisa langsung digeser; klik ganda = mode ketik.
            let drag = null;
            frontEls.forEach(function (el) {
                el.addEventListener('pointerdown', function (e) {
                    if (editing === el) return; // sedang mengetik → biarkan seleksi teks normal
                    const wasSelected = selected === el;
                    select(el);
                    // Sentuhan pertama di elemen yang belum terpilih cuma memilih (supaya halaman
                    // tetap bisa di-scroll di HP); geser mulai dari sentuhan berikutnya.
                    if (e.pointerType === 'touch' && !wasSelected) return;
                    e.preventDefault();
                    const s = state.get(el).style;
                    drag = { el: el, id: e.pointerId, x: e.clientX, y: e.clientY, top: s.top, left: s.left, moved: false };
                    el.setPointerCapture(e.pointerId);
                });
                el.addEventListener('pointermove', function (e) {
                    if (!drag || drag.el !== el || drag.id !== e.pointerId) return;
                    const dx = e.clientX - drag.x;
                    const dy = e.clientY - drag.y;
                    if (!drag.moved && Math.abs(dx) + Math.abs(dy) < 3) return;
                    drag.moved = true;
                    el.classList.add('dragging');
                    const st = state.get(el);
                    let left = Math.max(0, Math.min(PAGE_W - st.width, drag.left + dx));
                    const top = Math.max(0, Math.min(PAGE_H - el.offsetHeight, drag.top + dy));
                    // Snap ke tengah horizontal halaman.
                    const center = left + st.width / 2;
                    const snapped = Math.abs(center - PAGE_W / 2) < 6;
                    if (snapped) left = PAGE_W / 2 - st.width / 2;
                    guide.classList.toggle('show', snapped);
                    st.style.left = Math.round(left * 10) / 10;
                    st.style.top = Math.round(top * 10) / 10;
                    applyStyle(el);
                });
                function endDrag(e) {
                    if (!drag || drag.el !== el) return;
                    if (drag.moved) markDirty();
                    el.classList.remove('dragging');
                    guide.classList.remove('show');
                    try { el.releasePointerCapture(e.pointerId); } catch (_) {}
                    drag = null;
                }
                el.addEventListener('pointerup', endDrag);
                el.addEventListener('pointercancel', endDrag);
                el.addEventListener('dblclick', function () { enterEdit(el); });
                el.addEventListener('input', markDirty);
                el.addEventListener('keydown', function (e) {
                    if (editing !== el) return;
                    // Enter selesai mengetik (kecuali kalimat kegiatan: Enter = baris baru).
                    if (e.key === 'Escape' || (e.key === 'Enter' && !e.shiftKey && el.dataset.key !== 'kegiatan')) {
                        e.preventDefault();
                        exitEdit();
                    }
                });
                el.addEventListener('focusout', function () {
                    if (editing === el) setTimeout(function () { if (editing === el && !el.contains(document.activeElement)) exitEdit(); }, 0);
                });
            });

            // Panah keyboard = geser halus (Shift = 10px) saat elemen terpilih dan tidak sedang mengetik.
            document.addEventListener('keydown', function (e) {
                if (!selected || editing || !e.key.startsWith('Arrow')) return;
                if (e.target.closest && e.target.closest('input, select, textarea, [contenteditable="true"]')) return;
                e.preventDefault();
                const step = e.shiftKey ? 10 : 1;
                const st = state.get(selected);
                if (e.key === 'ArrowLeft') st.style.left = Math.max(0, st.style.left - step);
                if (e.key === 'ArrowRight') st.style.left = Math.min(PAGE_W - st.width, st.style.left + step);
                if (e.key === 'ArrowUp') st.style.top = Math.max(0, st.style.top - step);
                if (e.key === 'ArrowDown') st.style.top = Math.min(PAGE_H - selected.offsetHeight, st.style.top + step);
                applyStyle(selected);
                markDirty();
            });

            // Klik di luar elemen & panel = batal pilih.
            document.addEventListener('pointerdown', function (e) {
                if (!selected) return;
                if (e.target.closest('[data-front]') || e.target.closest('#stylebar')) return;
                deselect();
            });

            // ===== Simpan / Reset =====
            const btnSave = document.getElementById('btn-save');
            const btnReset = document.getElementById('btn-reset');
            const saveUrl = @js(route('interns.certificate.override.save', $intern));
            const resetUrl = @js(route('interns.certificate.override.reset', $intern));
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const statusEl = document.getElementById('save-status');
            const metaEl = document.getElementById('edit-meta');

            function setStatus(text, kind) {
                statusEl.textContent = text;
                statusEl.className = 'save-status' + (kind ? ' ' + kind : '');
            }

            function setMeta(meta) {
                if (meta && meta.updated_at) {
                    metaEl.textContent = '';
                    metaEl.append('Terakhir diedit oleh ');
                    const who = document.createElement('b');
                    who.textContent = meta.updated_by || 'pengguna dihapus';
                    metaEl.append(who, ' · ' + meta.updated_at);
                } else {
                    metaEl.textContent = metaEl.dataset.empty;
                }
            }

            // Halaman belakang: input teks biasa.
            document.addEventListener('input', function (e) {
                if (e.target.closest && e.target.closest('#page-back [data-key][contenteditable="true"]')) markDirty();
            });

            // Kumpulkan isi: halaman depan = objek {text + posisi + gaya}, halaman belakang = teks
            // (innerText, bukan innerHTML). Key "kriteria.<field>.<sub>" dijadikan objek bertingkat.
            function collect() {
                const payload = {};
                frontEls.forEach(function (el) {
                    payload[el.dataset.key] = Object.assign(
                        { text: el.querySelector('.txt').innerText.trim() },
                        state.get(el).style
                    );
                });
                document.querySelectorAll('#page-back [data-key][contenteditable="true"]').forEach(function (el) {
                    const parts = el.dataset.key.split('.');
                    let target = payload;
                    for (let i = 0; i < parts.length - 1; i++) {
                        target[parts[i]] = target[parts[i]] || {};
                        target = target[parts[i]];
                    }
                    target[parts[parts.length - 1]] = el.innerText.trim();
                });
                return payload;
            }

            async function send(url, method, body) {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: body ? JSON.stringify(body) : undefined,
                });
                const data = await res.json().catch(function () { return {}; });
                if (!res.ok) {
                    const first = data.errors ? Object.values(data.errors)[0][0] : null;
                    throw new Error(first || data.message || ('HTTP ' + res.status));
                }
                return data;
            }

            async function save() {
                if (editing) exitEdit();
                if (document.activeElement) document.activeElement.blur();
                btnSave.disabled = true;
                setStatus('Menyimpan…');
                try {
                    const data = await send(saveUrl, 'POST', collect());
                    dirty = false;
                    setStatus(data.changed ? 'Tersimpan' : 'Tersimpan (sama dengan data asli)', 'ok');
                    setMeta(data.meta);
                } catch (err) {
                    setStatus('Gagal: ' + err.message, 'err');
                } finally {
                    btnSave.disabled = false;
                }
            }

            btnSave.addEventListener('click', save);

            // Ctrl+S / Cmd+S = Simpan.
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    save();
                }
            });

            btnReset.addEventListener('click', async function () {
                if (!confirm('Semua editan tersimpan (teks, posisi, dan gaya) akan dihapus dan sertifikat kembali ke data asli. Lanjutkan?')) return;
                btnReset.disabled = true;
                setStatus('Mereset…');
                try {
                    await send(resetUrl, 'DELETE');
                    dirty = false;
                    window.location.reload();
                } catch (err) {
                    setStatus('Gagal: ' + err.message, 'err');
                    btnReset.disabled = false;
                }
            });

            window.addEventListener('beforeunload', function (e) {
                if (!dirty) return;
                e.preventDefault();
                e.returnValue = '';
            });
        })();
    </script>
</body>
</html>
