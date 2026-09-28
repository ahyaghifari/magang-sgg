@php
    use App\Models\Intern;

    // Semua teks diambil dari $content = data asli digabung editan tersimpan
    // (App\Support\CertificateContent::for). Tiap elemen yang bisa diedit punya data-key =
    // nama field-nya, dipakai tombol Simpan untuk mengumpulkan teks.
    // $ed(key) → atribut data-key (+ contenteditable kalau user boleh mengedit).
    $ed = fn (string $key) => 'data-key="' . e($key) . '"' . ($canEdit ? ' contenteditable="true" spellcheck="false"' : '');

    $colorFor = fn (?string $rating) => match (trim((string) $rating)) {
        'Excellent' => '#1c8a4d',
        'Good' => '#0b47a1',
        'Fair' => '#b45309',
        'Below Average' => '#c2410c',
        default => '#b91c1c',
    };

    $isOverridden = fn (string $key) => in_array($key, $content['overridden'], true);
    $company = $intern->unit->company->name ?? 'Syifa Global Group';
    $meta = $content['meta'];
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
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,700&display=swap" rel="stylesheet">
<style>
    /*
     * Editor sertifikat — murni untuk browser (bukan DomPDF), jadi bebas pakai flex dsb.
     * Kanvas tiap halaman 1123x794 px (= A4 landscape pada 96 dpi). Semua teks sertifikat
     * diposisikan absolut di atas kanvas, masing-masing elemen terpisah & bisa diedit.
     * Toolbar dan sorotan hover/fokus tidak ikut ke PDF (kelas .exporting) maupun print.
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
    }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) {
            --app-bg: #0b1220;
            --bar-bg: #0f172a;
            --bar-text: #e2e8f0;
            --bar-muted: #94a3b8;
            --bar-border: #1e293b;
        }
    }
    :root[data-theme="dark"] {
        --app-bg: #0b1220;
        --bar-bg: #0f172a;
        --bar-text: #e2e8f0;
        --bar-muted: #94a3b8;
        --bar-border: #1e293b;
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { background: var(--app-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: #111827; }

    /* ===== Toolbar (tidak ikut PDF/print) ===== */
    .toolbar {
        position: sticky; top: 0; z-index: 10;
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        padding: 10px 16px; background: var(--bar-bg); border-bottom: 1px solid var(--bar-border);
        box-shadow: 0 2px 10px rgba(2, 6, 23, 0.06);
    }
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

    /* ===== Area halaman ===== */
    .pages { padding: 24px 16px 48px; overflow-x: auto; }
    .page {
        position: relative; width: 1123px; height: 794px; margin: 0 auto 24px;
        background: #ffffff; overflow: hidden; box-shadow: 0 8px 30px rgba(2, 6, 23, 0.18);
    }

    /* Sorotan teks yang bisa diedit — hanya di layar, dimatikan saat ekspor & print. */
    [contenteditable="true"] { outline: none; border-radius: 3px; transition: background-color .12s, box-shadow .12s; cursor: text; }
    [contenteditable="true"]:hover { box-shadow: 0 0 0 1px rgba(11, 71, 161, 0.45); }
    [contenteditable="true"]:focus { box-shadow: 0 0 0 2px #0b47a1; background-color: rgba(11, 71, 161, 0.06); }
    [contenteditable="true"]:empty::before { content: attr(data-placeholder); color: #94a3b8; font-style: italic; }
    .exporting [contenteditable="true"] { box-shadow: none !important; background-color: transparent !important; }
    .exporting [contenteditable="true"]:empty::before { content: none; }

    /* ===== Halaman 1: Sertifikat (ornamen dibuat ulang dari certificates/pkl.blade.php) ===== */
    .stripe { position: absolute; left: 0; right: 0; top: 0; height: 11px; background: var(--navy); }
    .stripe-green { position: absolute; left: 61.6%; right: 22%; top: 0; height: 11px; background: var(--green); }
    .stripe-magenta { position: absolute; left: 78%; right: 0; top: 0; height: 11px; background: var(--magenta); }
    .frame-outer { position: absolute; inset: 19px; border: 4px solid var(--navy); border-radius: 10px; }
    .frame-inner { position: absolute; inset: 25px; border: 1.5px solid var(--magenta); border-radius: 7px; }
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

    .f-title { position: absolute; top: 186px; left: 150px; right: 150px; text-align: center; font-size: 38px; font-weight: 800; color: var(--navy); letter-spacing: .5px; }
    .f-given { position: absolute; top: 246px; left: 200px; right: 200px; text-align: center; font-size: 14px; color: var(--body); }
    .f-name {
        position: absolute; top: 272px; left: 170px; right: 170px; text-align: center;
        font-family: 'Playfair Display', serif; font-style: italic; font-weight: 700; font-size: 44px; color: var(--blue);
        border-bottom: 2px solid var(--magenta); padding-bottom: 10px;
    }
    .f-school { position: absolute; top: 352px; left: 200px; right: 200px; text-align: center; font-size: 15px; font-weight: 600; color: var(--navy); }
    .f-activity { position: absolute; top: 388px; left: 175px; right: 175px; text-align: center; font-size: 14px; line-height: 1.7; color: var(--body); }
    .f-activity b { color: var(--navy); }
    .f-period { position: absolute; top: 486px; left: 250px; right: 250px; text-align: center; font-size: 13.5px; font-weight: 700; color: var(--navy); }
    .f-predikat {
        position: absolute; top: 518px; left: 50%; transform: translateX(-50%); white-space: nowrap;
        font-size: 14px; font-weight: 700; padding: 6px 20px; border-radius: 999px; border: 1.5px solid;
    }
    .f-predikat [data-key], .f-period [data-key] { display: inline-block; min-width: 1ch; }

    .f-sign { position: absolute; bottom: 72px; width: 270px; text-align: center; }
    .f-sign.left { left: 95px; }
    .f-sign.right { right: 95px; }
    .f-sign .line { border-top: 1.3px solid #94a3b8; padding-top: 7px; }
    .f-sign .who { font-weight: 700; color: var(--navy); font-size: 14px; }
    .f-sign .role { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
    .f-date { position: absolute; bottom: 150px; left: 95px; width: 270px; text-align: center; font-size: 12px; color: var(--body); }
    .f-no { position: absolute; bottom: 40px; left: 95px; font-size: 10.5px; display: flex; gap: 8px; }
    .f-no .label { color: var(--muted); letter-spacing: 1.2px; font-weight: 700; }
    .f-no .value { color: var(--navy); font-weight: 700; letter-spacing: .4px; }

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
        .toolbar { display: none !important; }
        .pages { padding: 0; overflow: visible; }
        .page { margin: 0; box-shadow: none; page-break-after: always; break-after: page; }
        .page:last-child { page-break-after: auto; break-after: auto; }
        [contenteditable="true"] { box-shadow: none !important; background: transparent !important; }
        [contenteditable="true"]:empty::before { content: none; }
    }
</style>
</head>
<body>
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Editor Sertifikat &middot; {{ $intern->nama }}</div>
            <div class="toolbar-hint">
                @if ($canEdit)
                    Klik teks untuk diedit, lalu tekan <b>Simpan</b> (atau Ctrl+S). Mengubah angka di sertifikat
                    <b>tidak</b> mengubah data penilaian aslinya.
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

            <div class="brand">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="Syifa Global Group">
                @endif
                <div class="brand-text"><b>SYIFA GLOBAL GROUP</b>INTERNSHIP</div>
            </div>

            <div class="f-title" {!! $ed('judul') !!}>{{ $content['judul'] }}</div>
            <div class="f-given" {!! $ed('pengantar') !!}>{{ $content['pengantar'] }}</div>
            <div class="f-name" {!! $ed('nama') !!}>{{ $content['nama'] }}</div>
            <div class="f-school" {!! $ed('sekolah') !!}>{{ $content['sekolah'] }}</div>
            {{-- Kalimat kegiatan: selama belum diedit, tampil dengan penebalan seperti PDF (teks
                 polosnya identik dengan data asli, jadi tidak ikut tersimpan sebagai editan). --}}
            <div class="f-activity" {!! $ed('kegiatan') !!}>@if ($isOverridden('kegiatan')){{ $content['kegiatan'] }}@else Atas partisipasi dan dedikasinya dalam menyelesaikan program <b>Praktik Kerja Lapangan (PKL)</b> di <b>{{ $company }}</b>. Semoga pengalaman ini menjadi bekal yang bermanfaat bagi pengembangan diri dan karier ke depan.@endif</div>
            <div class="f-period">Periode: <span {!! $ed('periode') !!}>{{ $content['periode'] }}</span></div>
            <div class="f-predikat" id="front-predikat" style="color: {{ $colorFor($content['predikat']) }}; border-color: {{ $colorFor($content['predikat']) }};">Predikat: <span id="front-predikat-value" {!! $ed('predikat') !!}>{{ $content['predikat'] }}</span></div>

            <div class="f-date" {!! $ed('tanggal') !!}>{{ $content['tanggal'] }}</div>
            <div class="f-sign left">
                <div class="line">
                    <div class="who" {!! $ed('ttd_nama') !!}>{{ $content['ttd_nama'] }}</div>
                    <div class="role" {!! $ed('ttd_jabatan') !!}>{{ $content['ttd_jabatan'] }}</div>
                </div>
            </div>
            <div class="f-sign right">
                <div class="line">
                    <div class="who" {!! $ed('ttd2_nama') !!}>{{ $content['ttd2_nama'] }}</div>
                    <div class="role" {!! $ed('ttd2_jabatan') !!}>{{ $content['ttd2_jabatan'] }}</div>
                </div>
            </div>

            <div class="f-no">
                <span class="label">NO. SERTIFIKAT</span>
                <span class="value" {!! $ed('no') !!}>{{ $content['no'] }}</span>
            </div>
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
                <tr><td class="label">Rating</td><td class="colon">:</td><td class="value" id="rating" style="color: {{ $colorFor($content['rating']) }};" {!! $ed('rating') !!}>{{ $content['rating'] }}</td></tr>
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
            const pages = Array.from(document.querySelectorAll('.page'));

            // Tempel selalu sebagai teks polos, supaya format dari Word/WhatsApp tidak ikut masuk.
            document.addEventListener('paste', function (e) {
                if (!e.target.closest || !e.target.closest('[contenteditable="true"]')) return;
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertText', false, text);
            });

            // Nilai per kriteria diubah → hitung ulang Total Score (rata-rata 10 kriteria) & Rating,
            // memakai batas predikat yang sama dengan sistem (Intern::predikat()).
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
                    const front = document.getElementById('front-predikat');
                    front.style.color = colors[rating];
                    front.style.borderColor = colors[rating];
                });
            });

            const btnPdf = document.getElementById('btn-pdf');
            btnPdf.addEventListener('click', async function () {
                const label = btnPdf.textContent;
                btnPdf.disabled = true;
                btnPdf.textContent = 'Membuat PDF…';
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
                            width: 1123,
                            height: 794,
                            windowWidth: 1123,
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
                if (document.activeElement) document.activeElement.blur();
                window.print();
            });

            // ===== Simpan / Reset (hanya untuk yang boleh mengedit) =====
            const btnSave = document.getElementById('btn-save');
            const btnReset = document.getElementById('btn-reset');
            if (!btnSave) return;

            const saveUrl = @js(route('interns.certificate.override.save', $intern));
            const resetUrl = @js(route('interns.certificate.override.reset', $intern));
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const statusEl = document.getElementById('save-status');
            const metaEl = document.getElementById('edit-meta');
            let dirty = false;

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

            document.addEventListener('input', function (e) {
                if (e.target.closest && e.target.closest('[data-key][contenteditable="true"]')) {
                    dirty = true;
                    setStatus('Belum disimpan', 'dirty');
                }
            });

            // Kumpulkan teks semua elemen ber-data-key (innerText, bukan innerHTML). Key berbentuk
            // "kriteria.<field>.<sub>" dijadikan objek bertingkat.
            function collect() {
                const payload = {};
                document.querySelectorAll('[data-key][contenteditable="true"]').forEach(function (el) {
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
                if (!confirm('Semua editan tersimpan akan dihapus dan sertifikat kembali ke data asli. Lanjutkan?')) return;
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
