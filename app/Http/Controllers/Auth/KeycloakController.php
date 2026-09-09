<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class KeycloakController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $kc = Socialite::driver('keycloak')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => 'Login SSO gagal. Coba lagi.']);
        }

        // Penghubung antar-sistem: EMAIL. Normalisasi ke lowercase.
        $email = strtolower(trim((string) $kc->getEmail()));

        if ($email === '') {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun SSO tidak mengirim email. Hubungi admin.',
            ]);
        }

        // Tidak auto-provision: user harus sudah ada di aplikasi ini.
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun SSO belum terdaftar di aplikasi ini. Hubungi admin.',
            ]);
        }

        // Aturan sama seperti login lokal: pendaftar mandiri harus disetujui admin dulu.
        if (! $user->isApproved() && ! $user->isAdmin()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun kamu belum disetujui oleh admin. Silakan tunggu konfirmasi.',
            ]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->put('sso', true);

        // Selaraskan hash password sesi — lihat catatan di App\Livewire\Auth\Login.
        $request->session()->put(
            'password_hash_'.config('auth.defaults.guard'),
            $user->getAuthPassword(),
        );

        return redirect()->intended(route('home'));
    }
}
