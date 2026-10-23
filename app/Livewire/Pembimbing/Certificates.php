<?php

namespace App\Livewire\Pembimbing;

use App\Models\Intern;
use App\Models\InternAppraisal;
use App\Support\CertificateAccess;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sertifikat PKL & Form Appraisal intern yang dibimbing/dimentori user yang sedang
 * login (lihat User::visibleInterns()) — bisa lihat/download kedua dokumen, dan
 * menilai 10 kriteria resmi (Intern::CRITERIA) lewat klik bintang 1-5, sama seperti
 * penilaian jurnal. Penilaian disimpan PER PENILAI (mentor, pembimbing, pimpinan —
 * tabel intern_appraisals); Nilai Akhir & Rating adalah akumulasi (rata-rata) semua
 * penilai yang sudah mengisi (lihat Intern::syncAppraisals()). Identitas (nama,
 * tanggal mulai/selesai, dll) TIDAK bisa diedit dari sini — tetap admin-only lewat
 * panel /admin.
 */
#[Layout('components.layouts.app')]
class Certificates extends Component
{
    use WithPagination;

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && CertificateAccess::hasFullAccess($user);
    }

    /** Intern yang boleh DILIHAT user yang sedang login (lihat User::visibleInterns()). */
    protected function visibleInterns()
    {
        return Intern::query();
    }

    /**
     * Beri nilai untuk satu kriteria — klik separuh bintang untuk nilai ",5" (mis. 4,5),
     * separuh lain untuk bilangan bulat (mis. 5). Sengaja pakai appraisableInterns(), BUKAN
     * visibleInterns() — Mentor bisa MELIHAT sertifikat semua intern, tapi cuma boleh
     * MENILAI intern yang memang dimentorinya. Pimpinan boleh menilai semua intern.
     * Nilai disimpan di baris penilaian milik role user ini saja.
     */
    public function rate(int $internId, string $field, float $stars): void
    {
        if (! array_key_exists($field, Intern::CRITERIA)) {
            return;
        }

        $intern = auth()->user()->appraisableInterns()->whereKey($internId)->first();

        if (! $intern) {
            return;
        }

        // Bulatkan ke kelipatan 0,5 terdekat supaya nilai selalu rapi (1, 1.5, 2, ... 5)
        // walau input yang dikirim dari klik separuh bintang seharusnya sudah tepat.
        $stars = round($stars * 2) / 2;
        $stars = max(Intern::SCALE_MIN, min(Intern::SCALE_MAX, $stars));

        $this->myAppraisal($intern)->fill([$field => $stars])->save();
    }

    /** Hapus nilai satu kriteria milik penilai ini (kembali ke "belum dinilai"). */
    public function clearRating(int $internId, string $field): void
    {
        if (! array_key_exists($field, Intern::CRITERIA)) {
            return;
        }

        $intern = auth()->user()->appraisableInterns()->whereKey($internId)->first();

        if (! $intern) {
            return;
        }

        $this->myAppraisal($intern)->fill([$field => null])->save();
    }

    /**
     * Update catatan penilaian (teks bebas) milik penilai ini — dipanggil dari UI inline-edit.
     * Mengembalikan pesan error (string) kalau gagal validasi, atau null kalau berhasil.
     */
    public function updateCatatan(int $internId, ?string $value): ?string
    {
        $intern = auth()->user()->appraisableInterns()->whereKey($internId)->first();

        if (! $intern) {
            return 'Intern tidak ditemukan atau bukan tanggung jawabmu.';
        }

        $validator = Validator::make(['value' => $value], [
            'value' => ['nullable', 'string', 'max:1000'],
        ], [], ['value' => 'catatan penilaian']);

        if ($validator->fails()) {
            return $validator->errors()->first('value');
        }

        $this->myAppraisal($intern)->fill([
            'catatan' => $value !== '' && $value !== null ? $validator->validated()['value'] : null,
        ])->save();

        return null;
    }

    /**
     * Baris penilaian milik user yang login untuk intern ini — satu baris per role
     * (mentor/pembimbing/pimpinan); admin dihitung sebagai pembimbing.
     */
    protected function myAppraisal(Intern $intern): InternAppraisal
    {
        $appraisal = InternAppraisal::firstOrNew(['intern_id' => $intern->id, 'role' => $this->myRole()]);
        $appraisal->user_id = auth()->id();

        return $appraisal;
    }

    protected function myRole(): string
    {
        $user = auth()->user();

        return match (true) {
            $user->isPimpinan() => 'pimpinan',
            $user->isMentor() => 'mentor',
            default => 'pembimbing',
        };
    }

    public function render()
    {
        return view('livewire.pembimbing.certificates', [
            'interns' => $this->visibleInterns()
                ->with(['unit.company', 'appraisals'])
                ->orderBy('nama')
                ->paginate(15),
            // Dipakai view untuk menyembunyikan tombol "Edit Penilaian" pada intern yang
            // cuma boleh DILIHAT (Mentor) tapi bukan mentee-nya sendiri.
            'manageableInternIds' => auth()->user()->appraisableInterns()->pluck('id')->all(),
            'criteria' => Intern::CRITERIA,
            'myRole' => $this->myRole(),
            'roles' => InternAppraisal::ROLES,
        ]);
    }
}
