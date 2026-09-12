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
            <h1 style="font-size: 1.2rem; font-weight: 700; text-align: center;">Daftar Akun</h1>
            <p class="text-sm" style="color: var(--text-muted); margin-top: 0.25rem; text-align: center;">Akun baru akan aktif setelah disetujui admin</p>
        </div>

        <form wire:submit="register" class="auth-card" style="padding: 1.5rem;">
            <div style="margin-bottom: 1.1rem;">
                <label for="name" class="form-label">Nama lengkap</label>
                <div style="position: relative;">
                    <i class="fa-regular fa-user"
                       style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--text-faint); font-size:0.875rem;"></i>
                    <input type="text" id="name" wire:model="name" autocomplete="name" autofocus
                           class="form-input" style="padding-left: 2.4rem;" placeholder="Nama sesuai identitas">
                </div>
                @error('name')
                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-right:0.3rem;"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <div style="margin-bottom: 1.1rem;">
                <label for="email" class="form-label">Email</label>
                <div style="position: relative;">
                    <i class="fa-regular fa-envelope"
                       style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--text-faint); font-size:0.875rem;"></i>
                    <input type="email" id="email" wire:model="email" autocomplete="email"
                           class="form-input" style="padding-left: 2.4rem;" placeholder="nama@email.com">
                </div>
                @error('email')
                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-right:0.3rem;"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <div style="margin-bottom: 1.1rem;">
                <label for="institusi_id" class="form-label">Institusi / Sekolah / Kampus</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-building"
                       style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--text-faint); font-size:0.875rem; pointer-events:none;"></i>
                    <select id="institusi_id" wire:model="institusi_id" class="form-input" style="padding-left: 2.4rem;">
                        <option value="">— Pilih institusi —</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($institutions->isEmpty())
                    <p class="text-sm" style="color:#d97706; margin-top:0.4rem;">
                        <i class="fa-solid fa-circle-info" style="margin-right:0.3rem;"></i>Belum ada data institusi. Hubungi admin untuk menambahkannya lebih dulu.
                    </p>
                @endif
                @error('institusi_id')
                    <p class="text-sm" style="color:#dc2626; margin-top:0.4rem;">
                        <i class="fa-solid fa-circle-exclamation" style="margin-right:0.3rem;"></i>{{ $message }}
                    </p>
                @enderror
            </div>

            <div style="margin-bottom: 1.1rem;">
                <label for="jenis_kelamin" class="form-label">Jenis kelamin</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-venus-mars"
                       style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--text-faint); font-size:0.875rem; pointer-events:none;"></i>
                    <select id="jenis_kelamin" wire:model="jenis_kelamin" class="form-input" style="padding-left: 2.4rem;">
                        <option value="">— Pilih —</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                @error('jenis_kelamin')
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
                           autocomplete="new-password" class="form-input" style="padding-left: 2.4rem; padding-right: 2.6rem;"
                           placeholder="Minimal 8 karakter">
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

            <div style="margin-bottom: 1.35rem;">
                <label for="password_confirmation" class="form-label">Ulangi kata sandi</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock"
                       style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:var(--text-faint); font-size:0.875rem;"></i>
                    <input type="password" id="password_confirmation" wire:model="password_confirmation"
                           autocomplete="new-password" class="form-input" style="padding-left: 2.4rem;"
                           placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width:100%; padding-top:0.7rem; padding-bottom:0.7rem;"
                    wire:loading.attr="disabled" wire:target="register">
                <span wire:loading.remove wire:target="register">
                    <i class="fa-solid fa-user-plus" style="margin-right:0.45rem;"></i>Daftar
                </span>
                <span wire:loading wire:target="register">
                    <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.45rem;"></i>Memproses...
                </span>
            </button>
        </form>

        <p class="text-sm" style="text-align:center; color:var(--text-muted); margin-top:1.5rem;">
            Sudah punya akun?
            <a href="{{ route('login') }}" wire:navigate style="font-weight:600; color:var(--brand);">Masuk di sini</a>
        </p>
    </div>
    </div>
</div>
