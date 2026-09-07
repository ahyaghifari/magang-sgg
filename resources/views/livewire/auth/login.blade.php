<div class="flex min-h-full items-center justify-center" style="padding: 3rem 1rem;">
    <div style="width: 100%; max-width: 400px;">

        {{-- theme toggle --}}
        <div class="flex justify-end" style="margin-bottom: 1rem;">
            <button type="button" class="theme-toggle" onclick="toggleTheme()" aria-label="Ganti tema">
                <i class="fa-solid fa-circle-half-stroke"></i>
            </button>
        </div>

        {{-- brand --}}
        <div class="flex items-center justify-center" style="flex-direction: column; margin-bottom: 1.75rem;">
            <span class="flex items-center justify-center"
                  style="width: 52px; height: 52px; border-radius: 15px; background: linear-gradient(135deg,#042c6c 0%,#0b47a1 100%); color:#fff; font-size:1.25rem; box-shadow: 0 8px 20px -6px rgba(4,44,108,.5);">
                <i class="fa-solid fa-book-open-reader"></i>
            </span>
            <h1 style="margin-top: 0.9rem; font-size: 1.25rem; font-weight: 700; text-align: center; line-height: 1.3;">Magang<br>Syifa Global Group</h1>
            <p class="text-sm" style="color: var(--text-muted); margin-top: 0.35rem;">Masuk untuk mengisi jurnal harian</p>
        </div>

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

        <p class="text-sm" style="text-align:center; color:var(--text-muted); margin-top:1.5rem;">
            Admin?
            <a href="/admin/login" style="font-weight:600; color:var(--brand);">Masuk lewat panel admin</a>
        </p>
    </div>
</div>
