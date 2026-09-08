<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        // Akun hasil pendaftaran mandiri harus disetujui admin dulu (admin sendiri dikecualikan).
        $user = Auth::user();

        if (! $user->isApproved() && ! $user->isAdmin()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun kamu belum disetujui oleh admin. Silakan tunggu konfirmasi.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        // Selaraskan hash password di sesi dengan user yang baru login. Panel /admin memakai
        // middleware AuthenticateSession; kalau nilai ini masih milik sesi admin sebelumnya di
        // browser yang sama, membuka /admin akan me-logout sesi ini lalu "cookie ingat saya"
        // milik akun admin lama ikut aktif — akun terlihat berganti sendiri.
        session()->put(
            'password_hash_' . config('auth.defaults.guard'),
            $user->getAuthPassword(),
        );

        return $this->redirectIntended(route('home'), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), maxAttempts: 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
