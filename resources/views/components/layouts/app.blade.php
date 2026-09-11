<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Magang Syifa Global Group' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="icon" href="{{ \App\Support\Brand::logoUrl() }}">

    {{-- Theme: apply the saved/system preference before styles paint, then re-apply after every
         wire:navigate — Livewire morphs <html> back to the server markup (no `dark` class),
         which would otherwise reset the theme until the toggle is pressed. --}}
    <script>
        (function () {
            function applyTheme() {
                try {
                    var t = localStorage.getItem('theme');
                    var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', dark);
                } catch (e) {}
            }
            window.toggleTheme = function () {
                var isDark = document.documentElement.classList.toggle('dark');
                try { localStorage.setItem('theme', isDark ? 'dark' : 'light'); } catch (e) {}
            };
            applyTheme();
            document.addEventListener('livewire:navigated', applyTheme);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site h-full">
    @auth
        @php($portalUser = auth()->user())
        <div class="portal-shell" x-data="{ nav: false }" @keydown.escape.window="nav = false">

            {{-- Mobile drawer backdrop --}}
            <div class="portal-scrim" x-show="nav" x-cloak x-transition.opacity @click="nav = false"></div>

            {{-- ===== Sidebar ===== --}}
            <aside class="portal-sidebar" :class="{ 'is-open': nav }">
                <a href="{{ route('home') }}" wire:navigate class="portal-brand">
                    <x-app-logo class="portal-brand-logo" />
                    <span class="portal-brand-tagline">Syifa Global Group Magang</span>
                </a>

                <nav class="portal-nav">
                    <span class="portal-nav-label">Menu</span>
                    @if ($portalUser->isPortalMentor())
                        <a href="{{ route('pembimbing.activities') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.activities') ? 'active' : '' }}">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Kegiatan Intern</span>
                        </a>
                        <a href="{{ route('pembimbing.tasks') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.tasks') ? 'active' : '' }}">
                            <i class="fa-solid fa-clipboard-list"></i>
                            <span>Tugas Intern</span>
                        </a>
                        <a href="{{ route('pembimbing.attendance') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.attendance') ? 'active' : '' }}">
                            <i class="fa-solid fa-fingerprint"></i>
                            <span>Presensi Intern</span>
                        </a>
                        <a href="{{ route('pembimbing.leaves') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.leaves') ? 'active' : '' }}">
                            <i class="fa-solid fa-calendar-xmark"></i>
                            <span>Izin Intern</span>
                        </a>
                    @else
                        <a href="{{ route('journals.index') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('journals.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-book"></i>
                            <span>Jurnal Harian</span>
                        </a>
                        <a href="{{ route('tasks.index') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-clipboard-list"></i>
                            <span>Tugas</span>
                        </a>
                        <a href="{{ route('leaves.index') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-calendar-xmark"></i>
                            <span>Izin</span>
                        </a>
                    @endif
                </nav>

                <div class="portal-sidebar-foot">
                    <div class="portal-user flex items-center justify-between">
                        <div class="flex items-center" style="gap:0.65rem; min-width:0;">
                            <span class="portal-user-avatar">{{ strtoupper(substr($portalUser->name, 0, 1)) }}</span>
                            <span style="min-width:0; line-height:1.25;">
                                <span style="display:block; font-weight:700; font-size:0.82rem; color:var(--text-heading); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $portalUser->name }}</span>
                                <span style="display:block; font-size:0.72rem; color:var(--text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $portalUser->email }}</span>
                            </span>
                        </div>
                        @unless ($portalUser->isPortalMentor())
                            <a href="{{ route('home') }}" wire:navigate @click="nav = false"
                               class="portal-icon-btn" style="flex-shrink:0;" aria-label="Beranda">
                                <i class="fa-solid fa-house"></i>
                            </a>
                        @endunless
                    </div>
                    @if ($portalUser->canToggleIntern())
                        <form method="POST" action="{{ route('portal.toggle-intern-view') }}">
                            @csrf
                            @if ($portalUser->isViewingAsIntern())
                                <button type="submit" class="portal-logout">
                                    <i class="fa-solid fa-user-shield"></i>
                                    <span>Kembali ke Admin</span>
                                </button>
                            @else
                                <button type="submit" class="portal-logout">
                                    <i class="fa-solid fa-user-graduate"></i>
                                    <span>Lihat sebagai Intern</span>
                                </button>
                            @endif
                        </form>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="portal-logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </aside>

            {{-- ===== Content ===== --}}
            <div class="portal-content">
                <header class="portal-topbar">
                    <button type="button" class="portal-icon-btn portal-hamburger"
                            @click="nav = true" aria-label="Buka menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    <div class="portal-topbar-actions">
                        <button type="button" class="portal-icon-btn" onclick="toggleTheme()" aria-label="Ganti tema">
                            <i class="fa-solid fa-circle-half-stroke"></i>
                        </button>
                    </div>
                </header>

                <main class="portal-main">
                    {{ $slot }}
                </main>

                <footer style="text-align:center; padding:1.25rem 1rem 1.75rem; font-size:0.75rem; color:var(--text-faint);">
                    &copy; {{ date('Y') }} M.Nasywa Labib &middot; Seluruh hak cipta dilindungi.
                </footer>
            </div>
        </div>
    @else
        {{ $slot }}
        <footer style="text-align:center; padding:1.25rem 1rem 1.75rem; font-size:0.75rem; color:var(--text-faint);">
            &copy; {{ date('Y') }} M.Nasywa Labib &middot; Seluruh hak cipta dilindungi.
        </footer>
    @endauth

    <script>
        // Kompres foto di sisi klien sebelum diunggah (dipakai form isi jurnal).
        // Menyusutkan sisi terpanjang ke maxDim px lalu re-encode JPEG. Non-gambar / gagal → kembalikan file asli.
        window.compressImageFile = async function (file, opts) {
            opts = opts || {};
            var maxDim = opts.maxDim || 1600;
            var quality = opts.quality || 0.7;
            if (!file || !file.type || file.type.indexOf('image/') !== 0 || file.type === 'image/gif') {
                return file;
            }
            try {
                var bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
                var w = bitmap.width, h = bitmap.height;
                if (w > maxDim || h > maxDim) {
                    var scale = maxDim / Math.max(w, h);
                    w = Math.round(w * scale);
                    h = Math.round(h * scale);
                }
                var canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(bitmap, 0, 0, w, h);
                if (bitmap.close) bitmap.close();
                var blob = await new Promise(function (resolve) {
                    canvas.toBlob(resolve, 'image/jpeg', quality);
                });
                if (!blob || blob.size >= file.size) {
                    return file; // tidak ada penghematan — pakai yang asli
                }
                var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
            } catch (e) {
                return file;
            }
        };
    </script>
</body>
</html>
