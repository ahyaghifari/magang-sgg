<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">

    <title>{{ $title ?? 'Internship Syifa Global Group' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="icon" href="{{ \App\Support\Brand::faviconUrl() }}">

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
        @php
            $portalUser = auth()->user();
        @endphp
        <div class="portal-shell"
             x-data="{
                nav: false,
                confirmLogout: false,
                touchStartX: 0,
                touchStartY: 0,
                onTouchStart(e) {
                    this.touchStartX = e.touches[0].clientX;
                    this.touchStartY = e.touches[0].clientY;
                },
                onTouchEnd(e) {
                    let dx = e.changedTouches[0].clientX - this.touchStartX;
                    let dy = e.changedTouches[0].clientY - this.touchStartY;
                    if (Math.abs(dx) < 60 || Math.abs(dy) > 60) return;
                    if (dx > 0 && this.touchStartX < 40 && ! this.nav) {
                        this.nav = true;
                    } else if (dx < 0 && this.nav) {
                        this.nav = false;
                    }
                },
             }"
             @keydown.escape.window="nav = false"
             x-on:touchstart.passive="onTouchStart($event)"
             x-on:touchend="onTouchEnd($event)"
        >

            {{-- Mobile drawer backdrop --}}
            <div class="portal-scrim" x-show="nav" x-cloak x-transition.opacity @click="nav = false"></div>

            {{-- ===== Sidebar ===== --}}
            <aside class="portal-sidebar" :class="{ 'is-open': nav }">
                <a href="{{ route('home') }}" wire:navigate class="portal-brand">
                    <x-app-logo class="portal-brand-logo" />
                    <span class="portal-brand-tagline">Syifa Global Group Internship</span>
                </a>

                <nav class="portal-nav">
                    <span class="portal-nav-label">Menu</span>
                    @if ($portalUser->isPimpinan())
                        <a href="{{ route('pimpinan.dashboard') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pimpinan.dashboard') ? 'active' : '' }}">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span>Dashboard</span>
                        </a>
                    @elseif ($portalUser->isPortalMentor())
                        @php
                            // Jaring pengaman selain push notification (yang bisa gagal — izin ditolak,
                            // iOS butuh install ke Home Screen dulu, dsb): badge ini selalu akurat karena
                            // baca langsung dari database, tidak tergantung status subscribe notifikasi.
                            // Dibatasi ke intern yang memang ditugaskan ke user ini (lihat User::visibleInterns()).
                            $visibleInternIds = $portalUser->visibleInterns()->pluck('id');
                            $pendingLeavesCount = \App\Models\LeaveRequest::whereIn('intern_id', $visibleInternIds)->where('status', 'pending')->count();
                            $rejectedTasksCount = \App\Models\Task::whereIn('intern_id', $visibleInternIds)->where('status', 'rejected')->count();
                        @endphp
                        <a href="{{ route('pembimbing.activities') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.activities') ? 'active' : '' }}">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Kegiatan</span>
                        </a>
                        <a href="{{ route('pembimbing.tasks') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.tasks') ? 'active' : '' }}">
                            <i class="fa-solid fa-clipboard-list"></i>
                            <span>Tugas</span>
                            @if ($rejectedTasksCount > 0)
                                <span style="margin-left:auto; background:#dc2626; color:#fff; font-size:0.68rem; font-weight:700; line-height:1; padding:0.25rem 0.45rem; border-radius:999px; flex-shrink:0;">{{ $rejectedTasksCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('pembimbing.attendance') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.attendance') ? 'active' : '' }}">
                            <i class="fa-solid fa-fingerprint"></i>
                            <span>Presensi</span>
                        </a>
                        @if ($portalUser->canReviewLeaveRequests())
                            <a href="{{ route('pembimbing.leaves') }}" wire:navigate @click="nav = false"
                               class="portal-nav-link {{ request()->routeIs('pembimbing.leaves') ? 'active' : '' }}">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <span>Izin</span>
                                @if ($pendingLeavesCount > 0)
                                    <span style="margin-left:auto; background:#dc2626; color:#fff; font-size:0.68rem; font-weight:700; line-height:1; padding:0.25rem 0.45rem; border-radius:999px; flex-shrink:0;">{{ $pendingLeavesCount }}</span>
                                @endif
                            </a>
                        @endif
                        <a href="{{ route('pembimbing.interns') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.interns') ? 'active' : '' }}">
                            <i class="fa-solid fa-user-graduate"></i>
                            <span>Intern</span>
                        </a>
                        <a href="{{ route('pembimbing.certificates') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('pembimbing.certificates') ? 'active' : '' }}">
                            <i class="fa-solid fa-award"></i>
                            <span>Sertifikat</span>
                        </a>
                    @else
                        <a href="{{ route('home') }}" wire:navigate @click="nav = false"
                           class="portal-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                            <i class="fa-solid fa-house"></i>
                            <span>Beranda</span>
                        </a>
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
                            @php($portalAvatar = $portalUser->intern?->avatar_path)
                            <span class="portal-user-avatar" style="overflow:hidden;">
                                @if ($portalAvatar)
                                    <img src="{{ url('storage/' . $portalAvatar) }}" alt="Foto profil {{ $portalUser->name }}"
                                         style="width:100%; height:100%; object-fit:cover;">
                                @else
                                    {{ strtoupper(substr($portalUser->name, 0, 1)) }}
                                @endif
                            </span>
                            <span style="min-width:0; line-height:1.25;">
                                <span style="display:block; font-weight:700; font-size:0.82rem; color:var(--text-heading); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $portalUser->name }}</span>
                                <span style="display:block; font-size:0.72rem; color:var(--text-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $portalUser->email }}</span>
                            </span>
                        </div>
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
                    <form id="logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="button" @click="confirmLogout = true" class="portal-logout">
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

                <footer style="text-align:center; padding:1.25rem 1rem 1.75rem; font-size:0.85rem; font-weight:600; color:var(--text-body);">
                    &copy; {{ date('Y') }} M.Nasywa Labib &middot; Seluruh hak cipta dilindungi.
                </footer>
            </div>

            <x-photo-lightbox />

            {{-- Sengaja ditaruh sebagai sibling dari .portal-sidebar/.portal-content (bukan di
                 dalamnya) — .portal-sidebar punya CSS transform untuk drawer mobile, dan elemen
                 position:fixed di dalam ancestor ber-transform ikut terjebak di kotak ancestor
                 itu, bukan viewport, jadi modal ini tidak akan pernah benar-benar center di layar
                 kalau ditaruh di dalam sidebar. Tetap pakai x-data root di atas (confirmLogout)
                 supaya tombol "Keluar" di sidebar bisa membuka modal ini. --}}
            <div
                x-show="confirmLogout"
                x-cloak
                x-transition.opacity
                @keydown.escape.window="confirmLogout = false"
                class="overlay-center"
                style="position:fixed; inset:0; z-index:100; padding:1.25rem; background:rgba(2,6,23,0.55);"
            >
                <div
                    @click.outside="confirmLogout = false"
                    x-show="confirmLogout"
                    x-transition
                    class="surface-card"
                    style="width:100%; max-width:24rem; padding:1.5rem; text-align:center;"
                >
                    <div style="width:3rem; height:3rem; margin:0 auto 1rem; border-radius:9999px; background:#fee2e2; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-arrow-right-from-bracket" style="color:#dc2626; font-size:1.1rem;"></i>
                    </div>
                    <h2 style="font-size:1.05rem; font-weight:700; color:var(--text-heading);">Keluar dari akun?</h2>
                    <p class="text-sm" style="color:var(--text-muted); margin-top:0.35rem;">
                        Kamu perlu login kembali untuk mengakses portal ini.
                    </p>
                    <div class="flex items-center justify-center" style="gap:0.6rem; margin-top:1.35rem;">
                        <button type="button" @click="confirmLogout = false" class="btn-ghost" style="flex:1;">Batal</button>
                        <button type="submit" form="logout-form" class="btn-danger" style="flex:1;">Ya, Keluar</button>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{ $slot }}
        <footer style="text-align:center; padding:1.25rem 1rem 1.75rem; font-size:0.85rem; font-weight:600; color:var(--text-body);">
            &copy; {{ date('Y') }} M.Nasywa Labib &middot; Seluruh hak cipta dilindungi.
        </footer>
    @endauth

    <script>
        // Buka foto di lightbox global (bukan tab baru) — dipakai di semua tempat yang menampilkan
        // thumbnail foto (jurnal, bukti tugas). Lihat resources/views/components/photo-lightbox.blade.php.
        window.openLightbox = function (src, alt) {
            window.dispatchEvent(new CustomEvent('open-lightbox', { detail: { src: src, alt: alt || '' } }));
        };
    </script>

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
