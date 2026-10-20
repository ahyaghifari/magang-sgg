<?php

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Support\CertificateAccess;
use App\Support\CertificateContent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Satu dokumen PDF, 1 lembar (2 halaman ukuran sama, 280x196mm) supaya bisa
 * dicetak bolak-balik di satu lembar fisik:
 * - Halaman 1 (depan): Sertifikat PKL dekoratif.
 * - Halaman 2 (belakang): Form resmi Appraisal on the Job Training Result (10 kriteria).
 *
 * Kedua halaman sengaja dibuat dengan ukuran kanvas yang persis sama karena DomPDF
 * cuma bisa satu ukuran halaman per dokumen — lihat resources/views/certificates/pkl.blade.php.
 */
class InternCertificateController extends Controller
{
    public function view(Request $request, Intern $intern): Response
    {
        $pdf = $this->buildPdf($intern, $this->halaman($request), $request->boolean('putar'));

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->filename($intern, $this->halaman($request)) . '"',
        ]);
    }

    public function download(Request $request, Intern $intern): Response
    {
        $pdf = $this->buildPdf($intern, $this->halaman($request), $request->boolean('putar'));

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($intern, $this->halaman($request)) . '"',
        ]);
    }

    /** ?halaman=depan|belakang → satu halaman; selain itu (atau kosong) → dua halaman. */
    protected function halaman(Request $request): ?string
    {
        $halaman = $request->query('halaman');

        return in_array($halaman, ['depan', 'belakang'], true) ? $halaman : null;
    }

    protected function filename(Intern $intern, ?string $halaman = null): string
    {
        return 'Sertifikat-PKL-' . Str::slug($intern->nama) . ($halaman ? '-' . $halaman : '') . '.pdf';
    }

    /**
     * Editor sertifikat di browser: pratinjau 2 halaman A4 landscape yang tiap teksnya bisa
     * diedit langsung (contenteditable), lalu diunduh jadi PDF (html2canvas + jsPDF di sisi
     * browser) atau di-print. Editan TIDAK disimpan — refresh = kembali ke data database.
     * Aturan lihat sama dengan PDF; yang boleh mengedit hanya CertificateAccess::canEdit(),
     * selain itu (mis. intern pemilik) tampil baca-saja.
     */
    public function editor(Intern $intern): View
    {
        $user = auth()->user();

        CertificateAccess::authorizeView($user, $intern);

        return view('certificates.editor', [
            ...$this->certificateData($intern),
            // Teks sertifikat = data asli digabung editan tersimpan (tabel sertifikat_overrides).
            'content' => CertificateContent::for($intern),
            'canEdit' => CertificateAccess::canEdit($user, $intern),
            'filename' => 'sertifikat-pkl-' . Str::slug($intern->nama) . '.pdf',
        ]);
    }

    protected function buildPdf(Intern $intern, ?string $halaman = null, bool $putar = false): string
    {
        CertificateAccess::authorizeView(auth()->user(), $intern);

        // Folder cache font DomPDF (font TTF dari resources/fonts diproses ke sini sekali).
        File::ensureDirectoryExists(storage_path('fonts'));

        return Pdf::loadView('certificates.pkl', [
            ...$this->certificateData($intern),
            // Sama dengan editor: data asli digabung editan tersimpan (sertifikat_overrides),
            // jadi PDF yang diunduh intern/pembimbing/admin ikut berubah setelah disimpan.
            'content' => CertificateContent::for($intern),
            'halaman' => $halaman,
            'putar' => $putar,
        ])
            ->setPaper('a4', 'landscape')
            // Hanya huruf yang dipakai yang disematkan — font TTF (resources/fonts) bisa ratusan KB.
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    /** Data bersama untuk PDF DomPDF dan editor sertifikat. */
    protected function certificateData(Intern $intern): array
    {
        $intern->loadMissing(['institusi', 'unit.company', 'pembimbing', 'mentor']);

        // Logo terbaru (ikon portal) lebih dulu; logo lama hanya cadangan kalau filenya tidak ada.
        $logoPath = collect(['logo-simagang.png', 'syifa-logo.png', 'syifa-logo.jpg', 'syifa-logo.jpeg', 'syifa-logo.webp'])
            ->map(fn ($file) => public_path("images/{$file}"))
            ->first(fn ($path) => is_file($path));

        $logoDataUri = $logoPath
            ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        return [
            'intern' => $intern,
            'logoDataUri' => $logoDataUri,
            'nomor' => sprintf('%03d/SGG-INT/%s', $intern->id, now()->format('Y')),
            'tanggalTerbit' => Carbon::now()->translatedFormat('d F Y'),
        ];
    }
}
