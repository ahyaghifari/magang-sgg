<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Intern;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sertifikat PKL/magang untuk peserta — dibuat dari data Intern yang sudah ada
 * (institusi, unit/perusahaan penempatan, tanggal mulai/selesai, pembimbing).
 */
class InternCertificateController extends Controller
{
    public function view(Intern $intern): Response
    {
        [$pdf, $filename] = $this->buildPdf($intern);

        return $pdf->stream($filename);
    }

    public function download(Intern $intern): Response
    {
        [$pdf, $filename] = $this->buildPdf($intern);

        return $pdf->download($filename);
    }

    /**
     * @return array{0: PdfContract, 1: string}
     */
    protected function buildPdf(Intern $intern): array
    {
        $user = auth()->user();
        $isOwner = $intern->user_id === $user->id;

        // Admin boleh lihat/unduh sertifikat siapa saja; intern cuma boleh miliknya sendiri;
        // Pembimbing/Mentor boleh untuk intern yang memang ditugaskan ke mereka
        // (lihat User::visibleInterns()).
        $isSupervisor = $user->visibleInterns()->whereKey($intern->id)->exists();

        abort_unless($user->isSuperAdmin() || $isOwner || $isSupervisor, 403);

        $intern->loadMissing(['institusi', 'unit.company', 'pembimbing']);

        $logoPath = collect(['png', 'jpg', 'jpeg', 'webp'])
            ->map(fn ($ext) => public_path("images/syifa-logo.{$ext}"))
            ->first(fn ($path) => is_file($path));

        $logoDataUri = $logoPath
            ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $nomor = sprintf('%03d/SGG-INT/%s', $intern->id, now()->format('Y'));

        // Pimpinan perusahaan tempat intern ditempatkan (bukan pembimbing lapangan) —
        // dicari dari akun berperan Pimpinan yang company_id-nya sama dengan company
        // dari unit penempatan intern (lihat User::company()).
        $pimpinan = $intern->unit?->company_id
            ? User::where('role', UserRole::Pimpinan)->where('company_id', $intern->unit->company_id)->first()
            : null;

        $pdf = Pdf::loadView('certificates.pkl', [
            'intern' => $intern,
            'pimpinan' => $pimpinan,
            'logoDataUri' => $logoDataUri,
            'nomor' => $nomor,
            'tanggalTerbit' => Carbon::now()->translatedFormat('d F Y'),
        ])->setPaper([0, 0, 793.7, 566.9]); // 280mm x 196mm dalam pt (1mm = 2.8347pt)

        $filename = 'Sertifikat-PKL-' . Str::slug($intern->nama) . '.pdf';

        return [$pdf, $filename];
    }
}
