{{--
    Panel pengajuan perubahan jadwal shift milik INTERN — menempel di bawah layar begitu minimal satu
    tanggal dipilih. Intern tidak mengubah jadwal langsung: tombol "Ajukan Perubahan" membuka daftar
    per tanggal (x-shift-per-date, mode request) dengan satu kolom Alasan bersama, lalu
    submitBulkShiftRequests(entries, reason) di komponen Livewire induk.
    Props: options (ShiftTone::entryOptions), entries (isian bulan ini, untuk badge "saat ini").
    Status terbuka/tertutup diumumkan lewat event window 'shift-bar-mode' (dipakai spacer di halaman).
--}}
@props(['options' => [], 'entries' => collect()])

<div x-data="{ open: false }" x-init="$watch('open', o => $dispatch('shift-bar-mode', { mode: o ? 'perDate' : 'all' }))"
     x-show="$wire.selected.length > 0" style="display:none;" x-transition.opacity.duration.200ms wire:ignore.self
     x-on:shift-bulk-saved.window="open = false"
     class="shift-actionbar" role="region" aria-label="Ajukan perubahan jadwal tanggal yang dipilih">
    {{-- Isian saat ini (dibaca x-shift-per-date) — di luar wire:ignore supaya selalu segar. --}}
    <div id="shift-current-map" hidden data-map="{{ json_encode(\App\Support\ShiftTone::currentMap($entries)) }}"></div>

    <div style="max-width:56rem; margin:0 auto;">
        <div class="flex items-center justify-between" style="gap:0.5rem; flex-wrap:wrap;" :style="open ? { marginBottom: '0.6rem' } : { marginBottom: '0' }">
            <span style="font-size:0.9rem; font-weight:700; color:var(--text-heading);" aria-live="polite">
                <i class="fa-solid fa-calendar-check" style="color:var(--brand);" aria-hidden="true"></i>
                <span x-text="$wire.selected.length"></span> tanggal dipilih
            </span>
            <div class="flex items-center" style="gap:0.4rem;">
                <button type="button" x-show="! open" class="shift-pd-submit" @click="open = true">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Ajukan Perubahan
                </button>
                <button type="button" wire:click="clearSelection" @click="open = false" class="shift-btn-sm" aria-label="Batalkan pilihan tanggal">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Batal
                </button>
            </div>
        </div>

        <div x-show="open" x-cloak>
            <p class="text-sm" style="color:var(--text-muted); margin-bottom:0.5rem;">
                Pilih shift yang kamu inginkan untuk tiap tanggal. Jadwal baru berubah setelah disetujui mentor.
            </p>
            <x-shift-per-date :options="$options" mode="request" />
        </div>
    </div>
</div>
