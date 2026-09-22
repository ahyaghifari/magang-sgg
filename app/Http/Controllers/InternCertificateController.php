<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Intern;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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

    protected function buildPdf(Intern $intern): string
    {
        $user = auth()->user();
        $isOwner = $intern->user_id === $user->id;
        $isSupervisor = $user->visibleInterns()->whereKey($intern->id)->exists();

        // Admin boleh lihat/unduh sertifikat siapa saja; Pembimbing/Mentor boleh untuk
        // intern yang memang ditugaskan ke mereka (lihat User::visibleInterns()).
        abort_unless($user->isSuperAdmin() || $isOwner || $isSupervisor, 403);

        // Intern cuma boleh unduh sertifikat sendiri kalau magangnya sudah benar-benar
        // selesai (tanggal_selesai sudah lewat/hari ini) — admin & pembimbing/mentor
        // yang mengelola tetap boleh kapan pun untuk keperluan pratinjau/cetak manual.
        if ($isOwner && ! $user->isSuperAdmin() && ! $isSupervisor) {
            abort_unless(
                $intern->tanggal_selesai && $intern->tanggal_selesai->lte(now()),
                403,
                'Sertifikat baru bisa diakses setelah tanggal selesai magang.',
            );
        }

        $intern->loadMissing(['institusi', 'unit.company', 'pembimbing', 'mentor']);

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

        $tanggalTerbit = Carbon::now()->translatedFormat('d F Y');

        return Pdf::loadView('certificates.pkl', [
            'intern' => $intern,
            'pimpinan' => $pimpinan,
            'logoDataUri' => $logoDataUri,
            'nomor' => $nomor,
            'tanggalTerbit' => $tanggalTerbit,
        ])->output();
    }
}
