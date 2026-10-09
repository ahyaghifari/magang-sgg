{{--
    Pemilih peserta yang bisa dicari (Alpine, tanpa library tambahan). Tiap opsi: foto/inisial +
    nama + unit. Memilih → $wire.set('internId', id) (ikut tersimpan di ?intern=ID) dan memicu
    event window "shift-intern-picked" (dipakai daftar "Baru dilihat").

    Props: interns (koleksi Intern dengan relasi unit + kolom uses_shift),
           selected (Intern|null).
--}}
@props(['interns' => collect(), 'selected' => null])

@php
    $isRegistered = fn ($i) => (bool) $i->uses_shift;
    $groups = [
        'reg' => ['label' => 'Memakai jadwal shift', 'items' => $interns->filter($isRegistered)],
        'other' => ['label' => 'Peserta lainnya', 'items' => $interns->reject($isRegistered)],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'shift-picker']) }}
     x-data="{
        open: false,
        q: '',
        match(h) { return h.includes(this.q.toLowerCase().trim()) },
        groupHas(g) { return [...this.$refs.list.querySelectorAll('[data-opt]')].some(el => el.dataset.group === g && this.match(el.dataset.h)) },
        anyMatch() { return [...this.$refs.list.querySelectorAll('[data-opt]')].some(el => this.match(el.dataset.h)) },
        toggle() { this.open = ! this.open; if (this.open) this.$nextTick(() => this.$refs.search.focus()) },
        close() { this.open = false; this.q = '' },
        pick(id) {
            this.close();
            $wire.set('internId', String(id));
            window.dispatchEvent(new CustomEvent('shift-intern-picked', { detail: { id: String(id) } }));
        },
     }"
     @click.outside="close()" @keydown.escape.prevent.stop="close(); $refs.trigger.focus()">
    <label id="shift-picker-label" class="form-label">Peserta</label>
    <button type="button" x-ref="trigger" class="shift-picker-btn" @click="toggle()"
            aria-haspopup="listbox" :aria-expanded="open.toString()" aria-labelledby="shift-picker-label">
        @if ($selected)
            <x-shift-avatar :intern="$selected" />
            <span style="min-width:0; flex:1;">
                <span style="display:block; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $selected->nama }}</span>
                <span class="shift-muted-line" style="display:block;">{{ $selected->unit?->name ?? 'Unit belum diisi' }}</span>
            </span>
        @else
            <span class="shift-avatar"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
            <span style="flex:1; color:var(--text-muted);">Pilih peserta…</span>
        @endif
        <i class="fa-solid fa-chevron-down" style="color:var(--text-muted); transition:transform .15s;" :style="open && 'transform:rotate(180deg)'" aria-hidden="true"></i>
    </button>

    <div x-show="open" x-transition.opacity.duration.150ms style="display:none;" wire:ignore.self class="shift-picker-panel">
        <div style="padding:0.5rem; border-bottom:1px solid var(--border-soft);">
            <div style="position:relative;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); color:var(--text-faint); font-size:0.8rem;" aria-hidden="true"></i>
                <input type="search" x-ref="search" x-model="q" class="form-input" style="padding-left:2.1rem;"
                       placeholder="Cari nama atau unit…" aria-label="Cari peserta" autocomplete="off"
                       @keydown.arrow-down.prevent="$focus.within($refs.list).first()">
            </div>
        </div>
        <div x-ref="list" class="shift-picker-list" role="listbox" aria-labelledby="shift-picker-label"
             @keydown.arrow-down.prevent="$focus.within($refs.list).wrap().next()"
             @keydown.arrow-up.prevent="$focus.within($refs.list).wrap().previous()">
            @foreach ($groups as $key => $group)
                @if ($group['items']->isNotEmpty())
                    <p class="shift-picker-group" x-show="groupHas('{{ $key }}')">{{ $group['label'] }}</p>
                    @foreach ($group['items'] as $i)
                        <button type="button" data-opt data-group="{{ $key }}"
                                data-h="{{ mb_strtolower($i->nama . ' ' . ($i->unit?->name ?? '')) }}"
                                x-show="match($el.dataset.h)" @click="pick({{ $i->id }})"
                                class="shift-picker-opt" role="option" aria-selected="{{ $selected?->id === $i->id ? 'true' : 'false' }}">
                            <x-shift-avatar :intern="$i" />
                            <span style="min-width:0; flex:1;">
                                <span style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $i->nama }}</span>
                                <span class="shift-muted-line" style="display:block; font-weight:400;">{{ $i->unit?->name ?? 'Unit belum diisi' }}</span>
                            </span>
                            @if ($selected?->id === $i->id)
                                <i class="fa-solid fa-check" style="color:var(--brand);" aria-hidden="true"></i>
                            @endif
                        </button>
                    @endforeach
                @endif
            @endforeach
            <p x-show="! anyMatch()" style="display:none; padding:1rem; text-align:center; font-size:0.85rem; color:var(--text-muted);">
                Tidak ada peserta yang cocok.
            </p>
        </div>
    </div>
</div>
