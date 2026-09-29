<?php

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Models\SertifikatOverride;
use App\Support\CertificateAccess;
use App\Support\CertificateContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Simpan / reset editan teks sertifikat PKL dari editor sertifikat. Yang disimpan hanya
 * field yang berbeda dari data asli (CertificateContent::diff()). Hanya untuk tampilan
 * sertifikat — nilai penilaian asli di tabel interns TIDAK diubah.
 * Otorisasi sama dengan hak edit di editor: CertificateAccess::canEdit().
 */
class SertifikatOverrideController extends Controller
{
    public function store(Request $request, Intern $intern): JsonResponse
    {
        abort_unless(CertificateAccess::canEdit($request->user(), $intern), 403);

        $rules = ['kriteria' => ['nullable', 'array']];
        foreach (CertificateContent::FIELDS as $key => $max) {
            if (isset(CertificateContent::FRONT_LAYOUT[$key])) {
                // Halaman depan: objek {text, top, left, font, size, bold, italic, color}
                // (string polos versi lama juga masih diterima → dianggap teks saja).
                $rules[$key] = ['nullable'];
                $rules["{$key}.text"] = ['nullable', 'string', 'max:' . ($max * 2)];
                $rules["{$key}.top"] = ['nullable', 'numeric'];
                $rules["{$key}.left"] = ['nullable', 'numeric'];
                $rules["{$key}.size"] = ['nullable', 'numeric'];
                $rules["{$key}.font"] = ['nullable', 'string', 'max:30'];
                $rules["{$key}.color"] = ['nullable', 'string', 'max:7'];
                $rules["{$key}.bold"] = ['nullable', 'boolean'];
                $rules["{$key}.italic"] = ['nullable', 'boolean'];
            } else {
                $rules[$key] = ['nullable', 'string', 'max:' . ($max * 2)];
            }
        }
        foreach (array_keys(Intern::CRITERIA) as $field) {
            $rules["kriteria.{$field}"] = ['nullable', 'array'];
            foreach (CertificateContent::KRITERIA_FIELDS as $sub => $max) {
                $rules["kriteria.{$field}.{$sub}"] = ['nullable', 'string', 'max:' . ($max * 2)];
            }
        }
        // Batas validasi dilonggarkan 2x (spasi/baris ekstra dari browser); setelah dibersihkan,
        // CertificateContent::sanitize() tetap memotong ke panjang maksimum sebenarnya.
        $validated = $request->validate($rules);

        $diff = CertificateContent::diff($intern, CertificateContent::sanitize($validated));

        if ($diff === []) {
            // Semua teks sama dengan data asli → tidak perlu baris override sama sekali.
            $intern->sertifikatOverride()->delete();
        } else {
            SertifikatOverride::updateOrCreate(
                ['intern_id' => $intern->id],
                ['data' => $diff, 'updated_by' => $request->user()->id],
            );
        }

        return response()->json([
            'status' => 'saved',
            'changed' => $diff !== [],
            'meta' => $this->meta($intern),
        ]);
    }

    public function destroy(Request $request, Intern $intern): JsonResponse
    {
        abort_unless(CertificateAccess::canEdit($request->user(), $intern), 403);

        $intern->sertifikatOverride()->delete();

        return response()->json([
            'status' => 'reset',
            'meta' => $this->meta($intern),
        ]);
    }

    /** Info "terakhir diedit" untuk toolbar editor. */
    protected function meta(Intern $intern): array
    {
        $override = $intern->sertifikatOverride()->with('editor')->first();

        return [
            'updated_by' => $override?->editor?->name,
            'updated_at' => $override?->updated_at?->translatedFormat('d M Y, H:i'),
        ];
    }
}
