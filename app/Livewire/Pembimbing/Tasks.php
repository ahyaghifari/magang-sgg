<?php

namespace App\Livewire\Pembimbing;

use App\Livewire\Concerns\HasCommentThread;
use App\Models\Intern;
use App\Models\Task;
use App\Notifications\TaskAssigned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pemberian & daftar tugas untuk peserta magang — untuk peran pembimbing (dan admin).
 * Pembimbing bisa langsung memberi tugas lewat web (source = 'web') dan memantau
 * status tugas yang dicatat sendiri oleh intern (source = 'verbal').
 */
#[Layout('components.layouts.app')]
class Tasks extends Component
{
    use HasCommentThread, WithPagination;

    public string $internId = '';

    public string $status = '';

    /**
     * Filter cakupan (tombol cepat): '' = semua intern yang terlihat, 'mine' = hanya intern yang
     * langsung ditugaskan ke user ini sebagai pembimbing/mentor (assignedInternIds()).
     */
    #[Url]
    public string $scope = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    // ===== Form pemberian tugas =====
    public bool $showForm = false;

    /** Peserta yang dipilih di form "Beri Tugas" — bisa lebih dari satu kalau tugasnya sama. */
    public array $formInternIds = [];

    public string $title = '';

    public string $description = '';

    /**
     * Bagian opsional "Tugas berbeda untuk tiap peserta": catatan khusus per peserta,
     * [intern_id => teks]. Kosong = peserta itu memakai Keterangan umum saja. Hanya dipakai
     * kalau peserta terpilih >= 2 (lihat descriptionFor()).
     */
    public array $perInternNotes = [];

    public string $dueDate = '';

    public string $dueTime = '';

    // ===== Form edit tugas =====
    public ?int $editingTaskId = null;

    /** Semua tugas dalam satu kotak (batch) yang ikut diubah saat Edit — lihat openEditTask(). */
    public array $editingTaskIds = [];

    public string $editTitle = '';

    public string $editDescription = '';

    public string $editDueDate = '';

    public string $editDueTime = '';

    public function mount()
    {
        if (! $this->allowed()) {
            return $this->redirect(route('home'), navigate: true);
        }
    }

    public function updated($property): void
    {
        // Mis. tombol "Hapus pilihan" ($set formInternIds = []) → catatan khusus ikut dibuang.
        if ($property === 'formInternIds') {
            $this->pruneInternNotes();
        }

        // Ganti cakupan ke "bimbingan saya" → pilihan peserta yang bukan bimbingan dikosongkan.
        if ($property === 'scope' && $this->scope === 'mine' && $this->internId !== ''
            && ! in_array((int) $this->internId, $this->assignedInternIds(), true)) {
            $this->internId = '';
        }

        if (in_array($property, ['internId', 'status', 'scope', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }
    }

    public function resetDateFilter(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
        $this->resetPage();
    }

    protected function resolveCommentable(string $type, int $id): ?Model
    {
        if ($type !== 'task' || ! $this->allowed()) {
            return null;
        }

        // Sengaja pakai visibleInternIds() (BUKAN manageableTasksQuery()) — komentar tugas boleh
        // dikirim untuk SEMUA intern yang kelihatan (Mentor melihat semua intern), sama seperti
        // komentar jurnal di halaman Kegiatan. Aksi kelola tugas (edit/hapus/selesai) tetap sempit.
        return Task::whereKey($id)->whereIn('intern_id', $this->visibleInternIds())->first();
    }

    protected function allowed(): bool
    {
        $user = auth()->user();

        return $user && $user->isPortalMentor();
    }

    /**
     * Id intern yang boleh DILIHAT user yang sedang login — dipakai untuk daftar/filter
     * tugas (lihat User::visibleInterns(): Mentor sengaja melihat semua intern di sini).
     */
    protected function visibleInternIds(): array
    {
        return auth()->user()->visibleInterns()->pluck('id')->all();
    }

    /**
     * Id intern yang boleh DIKELOLA user yang sedang login — dipakai untuk pemberian
     * tugas baru dan setiap aksi yang mengubah tugas (edit/hapus/tandai selesai).
     * SELALU sempit ke intern yang memang dibimbing/dimentori (lihat User::manageableInterns()),
     * beda dari visibleInternIds() yang melebar untuk Mentor.
     */
    protected function manageableInternIds(): array
    {
        return auth()->user()->manageableInterns()->pluck('id')->all();
    }

    /**
     * Tugas yang boleh DIKELOLA (edit/hapus/tandai selesai) user yang sedang login:
     * milik intern yang memang dibimbing/dimentori, ATAU tugas yang dia SENDIRI berikan
     * (bisa lintas pembimbing, ke intern bukan mentee-nya) — orang yang membuat tugas
     * tetap boleh mengurus tugas buatannya sendiri.
     */
    protected function manageableTasksQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $manageableInternIds = $this->manageableInternIds();
        $myId = auth()->id();

        return Task::query()->where(
            fn ($q) => $q->whereIn('intern_id', $manageableInternIds)->orWhere('assigned_by', $myId)
        );
    }

    public function openForm(): void
    {
        $this->reset(['formInternIds', 'title', 'description', 'perInternNotes', 'dueDate', 'dueTime']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    /** Tambah satu peserta dari select "Peserta" ke pilihan (abaikan kalau sudah dipilih). */
    public function addFormIntern(int $internId): void
    {
        $ids = array_map('intval', $this->formInternIds);

        if (! in_array($internId, $ids, true)) {
            $this->formInternIds = [...$ids, $internId];
        }
    }

    /** Pilih/batal satu peserta di form "Beri Tugas" — dipakai tombol peserta terpilih untuk membatalkan. */
    public function toggleFormIntern(int $internId): void
    {
        $ids = array_map('intval', $this->formInternIds);

        $this->formInternIds = in_array($internId, $ids, true)
            ? array_values(array_diff($ids, [$internId]))
            : [...$ids, $internId];

        $this->pruneInternNotes();
    }

    /**
     * Buang catatan khusus milik peserta yang sudah tidak dipilih (peserta dibatalkan → isi
     * kotaknya dibuang). Catatan peserta lain yang masih dipilih tidak disentuh.
     */
    protected function pruneInternNotes(): void
    {
        $selected = array_map('intval', $this->formInternIds);

        $this->perInternNotes = array_filter(
            $this->perInternNotes,
            fn ($note, $internId) => in_array((int) $internId, $selected, true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** "Salin keterangan umum ke semua kotak" — isi kotak semua peserta terpilih dengan Keterangan umum. */
    public function copyGeneralToAll(): void
    {
        foreach (array_map('intval', $this->formInternIds) as $internId) {
            $this->perInternNotes[$internId] = $this->description;
        }
    }

    /**
     * Keterangan akhir untuk satu peserta:
     * - kotak khusus kosong (atau peserta terpilih < 2) → Keterangan umum (perilaku lama);
     * - kotak terisi + Keterangan umum terisi → Keterangan umum, baris kosong, lalu isi kotak;
     * - kotak terisi saja → isi kotak.
     * Catatan khusus sengaja diabaikan kalau tinggal 1 peserta, karena bagiannya tersembunyi di form
     * (supaya tidak ada teks yang ikut terkirim tanpa terlihat).
     */
    /**
     * Judul tugas boleh dikosongkan di form. Karena kolom tasks.title wajib terisi (struktur
     * database sengaja tidak diubah), judul kosong diisi otomatis: baris pertama keterangan
     * (maks. 80 karakter), atau "Tugas dari {nama pemberi}" kalau keterangan juga kosong.
     */
    protected function titleFor(?string $title, ?string $description): string
    {
        $title = trim((string) $title);

        if ($title !== '') {
            return $title;
        }

        $firstLine = trim(strtok(trim((string) $description), "\n") ?: '');

        return $firstLine !== ''
            ? Str::limit($firstLine, 80)
            : 'Tugas dari ' . (auth()->user()?->name ?? 'pembimbing');
    }

    protected function descriptionFor(int $internId): ?string
    {
        $general = trim($this->description);
        $note = count($this->formInternIds) >= 2 ? trim((string) ($this->perInternNotes[$internId] ?? '')) : '';

        $text = match (true) {
            $note === '' => $general,
            $general === '' => $note,
            default => $general . "\n\n" . $note,
        };

        return $text !== '' ? $text : null;
    }

    /** Tambahkan semua bimbingan/mentee sendiri ke pilihan, tanpa membuang peserta lain yang sudah dipilih. */
    public function selectAllMyInterns(): void
    {
        $this->formInternIds = array_values(array_unique([
            ...array_map('intval', $this->formInternIds),
            ...$this->manageableInternIds(),
        ]));
    }

    protected function rules(): array
    {
        return [
            // Sengaja TIDAK dibatasi ke manageableInternIds() — pemberian tugas boleh lintas
            // pembimbing, ke intern siapa pun di sistem (lihat allInterns() di render()).
            'formInternIds' => ['required', 'array', 'min:1'],
            'formInternIds.*' => ['integer', 'distinct', Rule::exists('interns', 'id')],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            // Catatan khusus per peserta — opsional, tidak wajib diisi.
            'perInternNotes' => ['array'],
            'perInternNotes.*' => ['nullable', 'string', 'max:2000'],
            'dueDate' => ['nullable', 'date'],
            'dueTime' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'formInternIds' => 'peserta',
            'formInternIds.*' => 'peserta',
            'title' => 'judul tugas',
            'description' => 'keterangan',
            'perInternNotes.*' => 'tugas khusus',
            'dueDate' => 'tanggal tenggat',
            'dueTime' => 'jam tenggat',
        ];
    }
    /**
     * Peserta terpilih di form "Beri Tugas" yang BUKAN bimbingan/mentee user yang login,
     * beserta pembimbing & mentor ASLI-nya — dipakai untuk kotak keterangan di bawah daftar
     * Peserta, supaya kalau tugas diberikan lintas pembimbing tetap jelas siapa pembimbing/
     * mentor sebenarnya. Kosong kalau semua yang dipilih memang bimbingan sendiri.
     */
    public function getSelectedOtherInternsProperty(): array
    {
        if ($this->formInternIds === []) {
            return [];
        }

        $myId = auth()->id();

        return Intern::with(['pembimbing', 'mentor'])
            ->whereIn('id', $this->formInternIds)
            ->where(fn ($q) => $q->where('pembimbing_id', '!=', $myId)->orWhereNull('pembimbing_id'))
            ->where(fn ($q) => $q->where('mentor_id', '!=', $myId)->orWhereNull('mentor_id'))
            ->orderBy('nama')
            ->get()
            ->map(fn (Intern $intern) => [
                'nama' => $intern->nama,
                'pembimbing' => $intern->pembimbing?->name,
                'mentor' => $intern->mentor?->name,
            ])
            ->all();
    }

    /** Gabungkan input tanggal + jam terpisah jadi satu nilai datetime (atau null kalau tanggal kosong). */
    protected function combineDueDateTime(string $date, string $time): ?string
    {
        if ($date === '') {
            return null;
        }

        return $date . ' ' . ($time !== '' ? $time : '00:00');
    }

    /**
     * Beri tugas ke SATU ATAU LEBIH peserta sekaligus. Tiap peserta dapat baris tugasnya
     * sendiri (bukan satu tugas bersama), supaya status selesai/ditolak, foto bukti, dan
     * komentar tetap terpisah per peserta.
     */
    public function assignTask(): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->validate();

        $interns = Intern::with('user')->whereIn('id', $this->formInternIds)->get();

        // Satu batch per pengiriman form — dipakai untuk menampilkan tugas yang sama jadi satu kotak.
        $batchId = (string) Str::uuid();

        foreach ($interns as $intern) {
            $task = Task::create([
                'intern_id' => $intern->id,
                'assigned_by' => auth()->id(),
                'batch_id' => $batchId,
                'title' => $this->titleFor($this->title, $this->descriptionFor($intern->id)),
                // Keterangan umum, ditambah catatan khusus peserta ini kalau diisi (bagian opsional).
                'description' => $this->descriptionFor($intern->id),
                'source' => 'web',
                'status' => 'pending',
                'due_date' => $this->combineDueDateTime($this->dueDate, $this->dueTime),
            ]);

            $intern->user?->notify(new TaskAssigned($task));
        }

        $this->closeForm();
        $this->dispatch('task-assigned', count: $interns->count());
    }

    /**
     * Buka modal edit — dipakai a.l. saat intern menunda tugas dan pembimbing mau menyesuaikannya.
     * $groupIds = semua tugas dalam satu kotak (tugas yang sama untuk beberapa
     * intern) — perubahan judul/keterangan/tenggat diterapkan ke semuanya sekaligus, tapi
     * hanya yang memang boleh dikelola user ini (manageableTasksQuery()).
     */
    public function openEditTask(int $taskId, array $groupIds = []): void
    {
        if (! $this->allowed()) {
            return;
        }

        $task = $this->manageableTasksQuery()->whereKey($taskId)->first();

        if (! $task) {
            return;
        }

        $this->editingTaskId = $taskId;
        $this->editingTaskIds = $this->manageableTasksQuery()
            ->whereKey(array_map('intval', $groupIds ?: [$taskId]))
            ->pluck('id')
            ->all();
        $this->editTitle = $task->title;
        $this->editDescription = $task->description ?? '';
        $this->editDueDate = $task->due_date?->format('Y-m-d') ?? '';
        $this->editDueTime = $task->due_date?->format('H:i') ?? '';
        $this->resetValidation();
    }

    public function closeEditTask(): void
    {
        $this->reset(['editingTaskId', 'editingTaskIds', 'editTitle', 'editDescription', 'editDueDate', 'editDueTime']);
        $this->resetValidation();
    }

    public function confirmEditTask(): void
    {
        if (! $this->allowed() || ! $this->editingTaskId) {
            return;
        }

        $task = $this->manageableTasksQuery()->whereKey($this->editingTaskId)->first();

        if (! $task) {
            return;
        }

        $this->validate([
            'editTitle' => ['nullable', 'string', 'max:255'],
            'editDescription' => ['nullable', 'string', 'max:2000'],
            'editDueDate' => ['nullable', 'date'],
            'editDueTime' => ['nullable', 'date_format:H:i'],
        ], [], [
            'editTitle' => 'judul tugas',
            'editDescription' => 'keterangan',
            'editDueDate' => 'tanggal tenggat',
            'editDueTime' => 'jam tenggat',
        ]);

        $this->manageableTasksQuery()
            ->whereKey($this->editingTaskIds ?: [$task->id])
            ->update([
                'title' => $this->titleFor($this->editTitle, $this->editDescription),
                'description' => $this->editDescription !== '' ? $this->editDescription : null,
                'due_date' => $this->combineDueDateTime($this->editDueDate, $this->editDueTime),
            ]);

        $this->closeEditTask();
    }

    public function markDone(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->manageableTasksQuery()->whereKey($taskId)
            ->update(['status' => 'done', 'completed_at' => now()]);
    }

    public function reopen(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->manageableTasksQuery()->whereKey($taskId)
            ->update(['status' => 'pending', 'completed_at' => null]);
    }

    public function delete(int $taskId): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->manageableTasksQuery()->whereKey($taskId)->delete();
    }

    /** Hapus satu kotak tugas sekaligus (tugas yang sama untuk beberapa intern). */
    public function deleteGroup(array $taskIds): void
    {
        if (! $this->allowed()) {
            return;
        }

        $this->manageableTasksQuery()->whereKey(array_map('intval', $taskIds))->delete();
    }

    /**
     * Tugas yang sama untuk beberapa intern ditampilkan jadi SATU kotak (lihat Task::groupKey()),
     * jadi paginasinya per kotak, bukan per tugas — supaya satu kotak tidak terpotong ke dua
     * halaman. Urutan: kotak yang masih ada tugas ditunda/belum/dikerjakan dulu, lalu terbaru.
     * Setiap item: ['key' => string, 'tasks' => Collection<Task> (urut nama intern)].
     */
    protected function paginateTaskGroups(\Illuminate\Database\Eloquent\Builder $base, int $perPage = 10): LengthAwarePaginator
    {
        $priority = ['rejected' => 0, 'pending' => 1, 'in_progress' => 2, 'done' => 3];

        $groups = (clone $base)
            ->get(['id', 'batch_id', 'assigned_by', 'source', 'title', 'description', 'due_date', 'created_at', 'status'])
            ->groupBy(fn (Task $task) => $task->groupKey())
            ->sortBy([
                fn ($a, $b) => $a->min(fn ($t) => $priority[$t->status] ?? 1) <=> $b->min(fn ($t) => $priority[$t->status] ?? 1),
                fn ($a, $b) => $b->max('created_at') <=> $a->max('created_at'),
            ])
            ->values();

        $page = $this->getPage();
        $pageGroups = $groups->forPage($page, $perPage);

        $tasks = Task::query()
            ->with(['intern.unit', 'assignedBy', 'comments.author.intern', 'completionPhotos'])
            ->whereKey($pageGroups->flatten()->pluck('id'))
            ->get()
            ->keyBy('id');

        $items = $pageGroups->map(fn ($group) => [
            'key' => $group->first()->groupKey(),
            'tasks' => $group->map(fn ($t) => $tasks->get($t->id))->filter()
                ->sortBy(fn ($t) => $t->intern?->nama)->values(),
        ])->values();

        return new LengthAwarePaginator($items, $groups->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /**
     * Intern yang LANGSUNG ditugaskan ke user ini (tercatat sebagai pembimbing_id atau mentor_id)
     * — dipakai tombol "Bimbingan/mentee saya". Beda dari manageableInterns() yang untuk admin
     * berarti semua intern.
     */
    protected function assignedInternIds(): array
    {
        $myId = auth()->id();

        return Intern::query()
            ->where(fn ($q) => $q->where('pembimbing_id', $myId)->orWhere('mentor_id', $myId))
            ->pluck('id')
            ->all();
    }

    public function render()
    {
        $visibleIds = $this->visibleInternIds();
        $manageableInternIds = $this->manageableInternIds();
        $assignedIds = $this->assignedInternIds();

        // Tombol cepat "Semua intern / Bimbingan/mentee saya" tampil untuk siapa pun yang punya
        // intern yang langsung ditugaskan kepadanya (Pembimbing, Mentor, maupun Admin yang juga
        // ditugaskan sebagai pembimbing/mentor).
        $canScope = $assignedIds !== [];
        $internIds = $canScope && $this->scope === 'mine'
            ? array_values(array_intersect($visibleIds, $assignedIds))
            : $visibleIds;

        $base = Task::query()
            ->whereIn('intern_id', $internIds)
            ->when($this->internId !== '', fn ($q) => $q->where('intern_id', $this->internId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        return view('livewire.pembimbing.tasks', [
            'taskGroups' => $this->paginateTaskGroups($base),
            'canScope' => $canScope,
            // Jumlah peserta di tiap tombol cepat "Semua intern" / "Bimbingan/mentee saya".
            'scopeCounts' => [
                'all' => count($visibleIds),
                'mine' => count(array_intersect($visibleIds, $assignedIds)),
            ],
            'interns' => Intern::whereIn('id', $internIds)->orderBy('nama')->get(['id', 'nama']),
            // Pilihan peserta di form "Beri Tugas" — SEMUA intern di sistem, lintas pembimbing
            // (bukan cuma mentee sendiri).
            // avatar_path ikut diambil untuk foto di kotak "Tugas berbeda untuk tiap peserta".
            'allInterns' => Intern::with('unit')->orderBy('nama')->get(['id', 'nama', 'unit_id', 'avatar_path']),
            // Dipakai buat sembunyikan tombol kelola (tandai selesai/edit/hapus) di
            // tugas milik intern yang cuma boleh DILIHAT (Mentor) bukan mentee sendiri — kecuali
            // tugas itu memang dia sendiri yang berikan (lihat manageableTasksQuery()).
            'manageableInternIds' => $manageableInternIds,
            'totalTasks' => (clone $base)->count(),
            'pendingTasks' => (clone $base)->where('status', '!=', 'done')->count(),
            'doneTasks' => (clone $base)->where('status', 'done')->count(),
        ]);
    }
}
