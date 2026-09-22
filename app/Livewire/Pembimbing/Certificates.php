<?php

namespace App\Livewire\Pembimbing;

use App\Models\Intern;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sertifikat PKL & Form Appraisal intern yang dibimbing/dimentori user yang sedang
 * login (lihat User::visibleInterns()) — bisa lihat/download kedua dokumen, dan
 * mengisi 10 kriteria penilaian resmi (Intern::CRITERIA, skala 1-4) plus data
 * identitas (nama, nama panggilan, tanggal mulai/selesai) langsung per-field
 * (tanpa modal — klik satu nilai, edit, simpan). Nilai Akhir & Rating dihitung
 * otomatis dari rata-ratanya (lihat Intern::booted()).
 */
#[Layout('components.layouts.app')]
class Certificates extends Component
{
    use WithPagination;

    /** Field identitas yang boleh diedit inline di sini — beda perlakuan dari kriteria penilaian. */
    private const IDENTITY_FIELDS = ['nama', 'nama_panggilan', 'tanggal_mulai', 'tanggal_selesai'];

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && $user->isPortalMentor();
    }

    /** Intern yang boleh DILIHAT user yang sedang login (lihat User::visibleInterns()). */
    protected function visibleInterns()
    {
        return auth()->user()->visibleInterns();
    }

    /**
     * Update satu field saja (dipanggil per-field dari UI inline-edit) — baik kriteria
     * penilaian, catatan, maupun identitas (nama/nama panggilan/tanggal mulai/selesai).
     * Mengembalikan pesan error (string) kalau gagal validasi, atau null kalau berhasil
     * — dipakai Alpine buat toggle UI.
     *
     * Sengaja pakai manageableInterns(), BUKAN visibleInterns() — Mentor bisa MELIHAT
     * sertifikat semua intern, tapi cuma boleh MENGEDIT (nilai maupun identitas) intern
     * yang memang dimentorinya.
     */
    public function updateField(int $internId, string $field, ?string $value): ?string
    {
        $isCriteria = array_key_exists($field, Intern::CRITERIA);
        $isCatatan = $field === 'catatan_penilaian';
        $isIdentity = in_array($field, self::IDENTITY_FIELDS, true);

        if (! $isCriteria && ! $isCatatan && ! $isIdentity) {
            return null;
        }

        $intern = auth()->user()->manageableInterns()->whereKey($internId)->first();

        if (! $intern) {
            return 'Intern tidak ditemukan atau bukan tanggung jawabmu.';
        }

        $labels = [
            'catatan_penilaian' => 'catatan penilaian',
            'nama' => 'nama',
            'nama_panggilan' => 'nama panggilan',
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
        ];

        $rules = match (true) {
            // Skala 1-4 — normalnya dipilih bulat lewat dropdown di UI, tapi tetap
            // mengizinkan desimal (mis. 3.75) untuk mode "ketik manual".
            $isCriteria => ['value' => ['nullable', 'numeric', 'min:1', 'max:4']],
            $isCatatan => ['value' => ['nullable', 'string', 'max:1000']],
            $field === 'nama' => ['value' => ['required', 'string', 'max:255']],
            $field === 'nama_panggilan' => ['value' => ['nullable', 'string', 'max:255']],
            default => ['value' => ['nullable', 'date']], // tanggal_mulai / tanggal_selesai
        };

        $label = $isCriteria ? Intern::CRITERIA[$field]['title'] : $labels[$field];

        $validator = Validator::make(['value' => $value], $rules, [], ['value' => $label]);

        if ($validator->fails()) {
            return $validator->errors()->first('value');
        }

        $validated = $validator->validated()['value'];
        $newValue = $value !== '' && $value !== null ? $validated : null;

        if ($field === 'tanggal_mulai' && $newValue && $intern->tanggal_selesai && $newValue > $intern->tanggal_selesai->toDateString()) {
            return 'Tanggal mulai tidak boleh setelah tanggal selesai.';
        }

        if ($field === 'tanggal_selesai' && $newValue && $intern->tanggal_mulai && $newValue < $intern->tanggal_mulai->toDateString()) {
            return 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
        }

        $update = [$field => $newValue];

        // dinilai_oleh/dinilai_pada cuma dicatat untuk field PENILAIAN (kriteria/catatan) —
        // bukan saat sekadar membetulkan identitas (nama/tanggal), sama seperti logika di
        // panel admin (lihat CreateIntern/EditIntern::mutateFormDataBeforeSave).
        if (! $isIdentity) {
            $update['dinilai_oleh'] = auth()->id();
            $update['dinilai_pada'] = now();
        }

        $intern->update($update);

        return null;
    }

    public function render()
    {
        return view('livewire.pembimbing.certificates', [
            'interns' => $this->visibleInterns()
                ->with('unit.company')
                ->orderBy('nama')
                ->paginate(15),
            // Dipakai view untuk menyembunyikan tombol "Edit Penilaian" pada intern yang
            // cuma boleh DILIHAT (Mentor) tapi bukan mentee-nya sendiri.
            'manageableInternIds' => auth()->user()->manageableInterns()->pluck('id')->all(),
            'criteria' => Intern::CRITERIA,
        ]);
    }
}
