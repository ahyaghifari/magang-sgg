<div>
    <div class="flex items-center justify-between" style="gap:1rem; margin-bottom:1.4rem;">
        <div>
            <h1 class="portal-title">Jurnal Harian</h1>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.2rem;">
                Riwayat kegiatan harian magang kamu
            </p>
        </div>
        @if ($intern)
            <button type="button" wire:click="$dispatch('open-journal-modal')" class="btn-primary" style="flex-shrink:0;">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Isi Jurnal</span>
            </button>
        @endif
    </div>

    <div x-data="{ show: false }" x-cloak
         x-on:journal-saved.window="show = true; setTimeout(() => show = false, 4000)"
         x-show="show" x-transition
         class="surface-card flex items-center"
         style="gap:0.7rem; padding:0.8rem 1rem; margin-bottom:1rem; border-color:#a7f3d0;">
        <i class="fa-solid fa-circle-check" style="color:var(--brand-success);"></i>
        <span class="text-sm" style="color:var(--text-body);">Jurnal berhasil disimpan.</span>
    </div>

    @if (! $intern)
        <div class="surface-card flex items-center" style="gap:0.85rem; padding:1rem 1.15rem;">
            <i class="fa-solid fa-circle-info" style="color:#d97706; font-size:1.1rem;"></i>
            <p class="text-sm" style="color:var(--text-body);">
                Akun kamu belum terhubung dengan data <strong>Intern</strong>.
                Hubungi admin terlebih dahulu.
            </p>
        </div>
    @else
        <div class="flex" style="flex-direction:column; gap:0.85rem;">
            @forelse ($journals as $journal)
                <article class="surface-card" style="padding:1.1rem 1.15rem;">
                    <div class="flex items-center justify-between" style="gap:0.75rem;">
                        <p style="font-size:0.8rem; font-weight:700; color:var(--brand);">
                            <i class="fa-regular fa-calendar-check" style="margin-right:0.4rem;"></i>
                            {{ \Illuminate\Support\Carbon::parse($journal->date)->translatedFormat('l, d F Y') }}
                        </p>
                        @if ($journal->attachments->isNotEmpty())
                            <span class="badge badge-neutral" style="flex-shrink:0;">
                                <i class="fa-solid fa-paperclip"></i> {{ $journal->attachments->count() }}
                            </span>
                        @endif
                    </div>

                    <p class="text-sm" style="margin-top:0.55rem; color:var(--text-body); white-space:pre-line;">{{ $journal->activity }}</p>

                    @if ($journal->attachments->isNotEmpty())
                        <div class="flex" style="flex-wrap:wrap; gap:0.5rem; margin-top:0.85rem;">
                            @foreach ($journal->attachments as $att)
                                @if ($att->type === 'link')
                                    <a href="{{ $att->url }}" target="_blank" rel="noopener"
                                       class="badge badge-neutral" style="text-decoration:none;">
                                        <i class="fa-solid fa-link"></i>
                                        {{ \Illuminate\Support\Str::limit($att->label ?: $att->url, 40) }}
                                    </a>
                                @elseif ($att->type === 'document')
                                    <a href="{{ url('storage/' . $att->path) }}" target="_blank" rel="noopener"
                                       class="badge badge-neutral" style="text-decoration:none;">
                                        <i class="fa-regular fa-file-pdf" style="color:#dc2626;"></i>
                                        {{ \Illuminate\Support\Str::limit($att->label ?: 'Dokumen PDF', 40) }}
                                    </a>
                                @else
                                    <a href="{{ url('storage/' . $att->path) }}" target="_blank" rel="noopener"
                                       style="display:block; border-radius:10px; overflow:hidden; border:1px solid var(--border);">
                                        <img src="{{ url('storage/' . $att->path) }}" alt="{{ $att->label }}"
                                             style="width:84px; height:84px; object-fit:cover; display:block;">
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    {{-- Penilaian pembimbing (rata-rata) --}}
                    <div class="flex items-center" style="gap:0.5rem; flex-wrap:wrap; margin-top:0.85rem; padding-top:0.7rem; border-top:1px solid var(--border-soft);">
                        <span class="text-sm" style="font-weight:600; color:var(--text-muted);">Penilaian pembimbing:</span>
                        @if ($journal->reviews_count)
                            <span style="color:#f59e0b; letter-spacing:1px;">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="fa-{{ $i <= round($journal->reviews_avg_rating) ? 'solid' : 'regular' }} fa-star"></i>
                                @endfor
                            </span>
                            <span class="text-sm" style="font-weight:700; color:var(--text-heading);">{{ number_format($journal->reviews_avg_rating, 1) }}</span>
                            <span class="text-sm" style="color:var(--text-muted);">({{ $journal->reviews_count }} pembimbing)</span>
                        @else
                            <span class="text-sm" style="color:var(--text-faint);">Belum dinilai</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="surface-card" style="padding:2.75rem 1.15rem; text-align:center;">
                    <i class="fa-regular fa-folder-open" style="font-size:1.6rem; color:var(--text-faint);"></i>
                    <p class="text-sm" style="margin-top:0.6rem; color:var(--text-muted);">
                        Belum ada jurnal. Mulai isi jurnal harianmu.
                    </p>
                    <button type="button" wire:click="$dispatch('open-journal-modal')" class="btn-primary"
                            style="margin-top:1rem;">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Isi Jurnal Sekarang</span>
                    </button>
                </div>
            @endforelse
        </div>

        @if ($journals->hasPages())
            <div class="flex items-center justify-between" style="margin-top:1.25rem;">
                <button wire:click="previousPage" class="btn-ghost" @disabled($journals->onFirstPage())>
                    <i class="fa-solid fa-chevron-left"></i> Sebelumnya
                </button>
                <span style="font-size:0.8rem; color:var(--text-muted);">
                    Halaman {{ $journals->currentPage() }} dari {{ $journals->lastPage() }}
                </span>
                <button wire:click="nextPage" class="btn-ghost" @disabled(! $journals->hasMorePages())>
                    Berikutnya <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        @endif
    @endif

    {{-- Modal isi jurnal --}}
    <livewire:journals.create />
</div>
