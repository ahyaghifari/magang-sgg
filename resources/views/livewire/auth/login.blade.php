<div class="auth-page">
    <x-auth-hero />

    <div class="auth-form-panel">
    <div style="width: 100%; max-width: 400px;">

        {{-- theme toggle --}}
        <div class="flex justify-end" style="margin-bottom: 1rem;">
            <button type="button" class="theme-toggle" onclick="toggleTheme()" aria-label="Ganti tema">
                <i class="fa-solid fa-circle-half-stroke"></i>
            </button>
        </div>

        {{-- brand (mobile only — desktop sudah ada di auth-hero) --}}
        <div class="flex items-center justify-center" style="flex-direction: column; margin-bottom: 1.75rem;">
            <h1 style="font-size: 1.2rem; font-weight: 700; text-align: center;">Masuk</h1>
            <p class="text-sm" style="color: var(--text-muted); margin-top: 0.25rem;"></p>
        </div>

        @if (session('status'))
            <div class="surface-card flex items-center"
                 style="gap:0.7rem; padding:0.8rem 1rem; margin-bottom:1rem; border-color:#a7f3d0;">
                <i class="fa-solid fa-circle-check" style="color:var(--brand-success);"></i>
                <span class="text-sm" style="color:var(--text-body);">{{ session('status') }}</span>
            </div>
        @endif

        <form wire:submit="login" class="auth-card" style="padding: 1.5rem;">
            <div style="margin-bottom: 1.1rem;">
                <label for="email" class="form-label">Email</label>
                <div style="position: relative;">
                    <i class="fa-regular fa-envelope"
                       style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--text-faint); font-size:0.875rem;"></i>
                    <input type="email" id="email" wire:model="email" autocomplete="email" autofocus
                           class="form-input" style="padding-left: 2.4rem;" placeholder="nama@email.com">
                </div>
                @error('email')
                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-right:0.3rem;"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <div style="margin-bottom: 1.1rem;">
                <label for="password" class="form-label">Kata sandi</label>
                <div style="position: relative;" x-data="{ show: false }">
                    <i class="fa-solid fa-lock"
                       style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--text-faint); font-size:0.875rem;"></i>
                    <input :type="show ? 'text' : 'password'" id="password" wire:model="password"
                           autocomplete="current-password" class="form-input" style="padding-left: 2.4rem; padding-right: 2.6rem;"
                           placeholder="••••••••">
                    <button type="button" @click="show = !show" tabindex="-1"
                            style="position:absolute; right:0.7rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.875rem;">
                        <i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-right:0.3rem;"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <label class="flex items-center text-sm" style="gap:0.5rem; color:var(--text-muted); margin-bottom:1.25rem;">
                <input type="checkbox" wire:model="remember"
                       style="width:1rem; height:1rem; border-radius:4px; accent-color:#042c6c;">
                Ingat saya di perangkat ini
            </label>

            <button type="submit" class="btn-primary" style="width:100%; padding-top:0.7rem; padding-bottom:0.7rem;"
                    wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">
                    <i class="fa-solid fa-right-to-bracket" style="margin-right:0.45rem;"></i>Masuk
                </span>
                <span wire:loading wire:target="login">
                    <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.45rem;"></i>Memproses...
                </span>
            </button>
        </form>

        {{-- ponytail: tombol selalu tampil. Kalau KEYCLOAK_* belum diisi, klik → halaman error Socialite.
             Sembunyikan lagi dengan @if(config('services.keycloak.base_url')) kalau mau aman saat SSO mati. --}}
        <div class="flex items-center" style="gap:0.75rem; margin:1.25rem 0 0.9rem;">
            <span style="flex:1; height:1px; background:var(--border, #e5e7eb);"></span>
            <span class="text-sm" style="color:var(--text-muted);">atau</span>
            <span style="flex:1; height:1px; background:var(--border, #e5e7eb);"></span>
        </div>
        <a href="{{ route('sso.redirect') }}" class="btn-primary"
           style="display:block; width:100%; text-align:center; padding:0.7rem 0; text-decoration:none;">
            <i class="fa-solid fa-key" style="margin-right:0.45rem;"></i>Masuk dengan SSO
        </a>

        <p class="text-sm" style="text-align:center; color:var(--text-muted); margin-top:1.5rem;">
            Belum punya akun?
            <a href="{{ route('register') }}" wire:navigate style="font-weight:600; color:var(--brand);">Daftar di sini</a>
        </p>
    </div>
    </div>
</div>
