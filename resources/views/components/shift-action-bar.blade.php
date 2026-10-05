{{--
    Action bar jadwal shift — menempel di bawah layar, muncul (dengan transisi) begitu minimal
    satu tanggal dipilih. Hanya dirender untuk peran yang boleh mengubah.
    Props: shifts. Memanggil apply('shift', id) / apply('off') / apply('clear') dan clearSelection()
    di komponen Livewire induk (yang punya properti $selected).
--}}
@props(['shifts' => collect()])

@php($tone = \App\Support\ShiftTone::class)

<div x-data x-show="$wire.selected.length > 0" style="display:none;" x-transition.opacity.duration.200ms wire:ignore.self
     class="shift-actionbar" role="region" aria-label="Terapkan jadwal ke tanggal yang dipilih">
    <div style="max-width:56rem; margin:0 auto;">
        <div class="flex items-center justify-between" style="gap:0.5rem; margin-bottom:0.6rem;">
            <span style="font-size:0.9rem; font-weight:700; color:var(--text-heading);" aria-live="polite">
                <i class="fa-solid fa-calendar-check" style="color:var(--brand);" aria-hidden="true"></i>
                <span x-text="$wire.selected.length"></span> tanggal dipilih
            </span>
            <button type="button" wire:click="clearSelection" class="shift-btn-sm" aria-label="Batalkan pilihan tanggal">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Batal
            </button>
        </div>
        <div class="flex" style="gap:0.45rem; flex-wrap:wrap;">
            @foreach ($shifts as $shift)
                @php($t = $tone::for($shift->code))
                <button type="button" wire:click="apply('shift', @js($shift->id ?? $shift->code))" wire:loading.attr="disabled" wire:target="apply"
                        class="shift-action-btn {{ $t['class'] }}" aria-label="Terapkan shift {{ $shift->label() }}">
                    <i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i> {{ $shift->code }}
                    <span style="font-weight:600; opacity:0.8;">{{ $tone::shortRange($shift) }}</span>
                </button>
            @endforeach
            @php($t = $tone::for('Libur'))
            <button type="button" wire:click="apply('off')" wire:loading.attr="disabled" wire:target="apply"
                    class="shift-action-btn {{ $t['class'] }}" aria-label="Tandai Libur">
                <i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i> Libur
            </button>
            <button type="button" wire:click="apply('clear')" wire:loading.attr="disabled" wire:target="apply"
                    wire:confirm="Kosongkan isian tanggal yang dipilih? Tanggal itu akan kembali mengikuti jam kerja biasa."
                    class="shift-action-btn is-neutral" aria-label="Kosongkan isian tanggal yang dipilih">
                <i class="fa-solid fa-eraser" aria-hidden="true"></i> Kosongkan
            </button>
        </div>
    </div>
</div>
