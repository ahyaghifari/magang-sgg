@props([
    'title',
    'description' => 'Tindakan ini tidak bisa dibatalkan.',
    'confirmWireClick',
    'confirmLabel' => 'Hapus',
])

{{--
    Konfirmasi hapus bergaya (bukan native confirm()/wire:confirm) — dipakai lewat slot:
    <x-confirm-delete title="Hapus tugas ini?" confirm-wire-click="delete({{ $task->id }})">
        <button type="button" @click="confirmOpen = true" ...>Hapus</button>
    </x-confirm-delete>
    Tombol trigger di slot WAJIB pakai @click="confirmOpen = true" — variabel itu didefinisikan
    oleh komponen ini di x-data pembungkusnya.
--}}
<div x-data="{ confirmOpen: false }" style="display:contents;">
    {{ $slot }}

    <div x-show="confirmOpen" x-cloak x-transition.opacity
         @keydown.escape.window="confirmOpen = false"
         class="overlay-center"
         style="position:fixed; inset:0; z-index:100; padding:1.25rem; background:rgba(2,6,23,0.55);">
        <div @click.outside="confirmOpen = false" x-show="confirmOpen" x-transition
             class="surface-card" style="width:100%; max-width:22rem; padding:1.35rem; text-align:center;">
            <div style="width:2.6rem; height:2.6rem; margin:0 auto 0.7rem; border-radius:9999px; background:#fee2e2; display:flex; align-items:center; justify-content:center;">
                <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
            </div>
            <h2 style="font-size:0.95rem; font-weight:700; color:var(--text-heading);">{{ $title }}</h2>
            <p class="text-sm" style="color:var(--text-muted); margin-top:0.25rem;">{{ $description }}</p>
            <div class="flex items-center justify-center" style="gap:0.5rem; margin-top:1.1rem;">
                <button type="button" @click="confirmOpen = false" class="btn-ghost" style="flex:1;">Batal</button>
                <button type="button" wire:click="{{ $confirmWireClick }}" @click="confirmOpen = false" class="btn-danger" style="flex:1;">{{ $confirmLabel }}</button>
            </div>
        </div>
    </div>
</div>
