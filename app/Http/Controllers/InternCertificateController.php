<?php

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Support\CertificateAccess;
use App\Support\CertificateContent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
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
    public function view(Intern $intern): Response
    {
        $pdf = $this->buildPdf($intern);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->filename($intern) . '"',
        ]);
    }

    public function download(Intern $intern): Response
    {
        $pdf = $this->buildPdf($intern);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($intern) . '"',
        ]);
    }

    protected function filename(Intern $intern): string
    {
        return 'Sertifikat-PKL-' . Str::slug($intern->nama) . '.pdf';
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

    protected function buildPdf(Intern $intern): string
    {
        CertificateAccess::authorizeView(auth()->user(), $intern);

        // Folder cache font DomPDF (font TTF dari resources/fonts diproses ke sini sekali).
        File::ensureDirectoryExists(storage_path('fonts'));

        return Pdf::loadView('certificates.pkl', [
            ...$this->certificateData($intern),
            // Sama dengan editor: data asli digabung editan tersimpan (sertifikat_overrides),
            // jadi PDF yang diunduh intern/pembimbing/admin ikut berubah setelah disimpan.
            'content' => CertificateContent::for($intern),
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

        $logoPath = collect(['png', 'jpg', 'jpeg', 'webp'])
            ->map(fn ($ext) => public_path("images/syifa-logo.{$ext}"))
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
