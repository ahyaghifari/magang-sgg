<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\Events\NotificationFailed;
use Syifa\KeycloakSso\Events\KeycloakLoginSucceeded;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama hari & bulan pada helper tanggal (translatedFormat) memakai Bahasa Indonesia.
        Carbon::setLocale('id');

        // Semua DatePicker panel admin tampil & diketik DD/MM/YYYY (picker bawaan browser mengikuti
        // bahasa perangkat, mis. "hh/bb/tttt"). Nilai yang disimpan tetap Y-m-d.
        \Filament\Forms\Components\DatePicker::configureUsing(fn (\Filament\Forms\Components\DatePicker $picker) => $picker
            ->native(false)
            ->displayFormat('d/m/Y')
            ->placeholder('DD/MM/YYYY')
            ->firstDayOfWeek(1)
            ->locale('id'));

        // Paket webpush diam-diam menelan push yang gagal terkirim (tidak ada exception) —
        // catat ke log supaya masalah seperti sertifikat SSL/kunci VAPID salah kelihatan.
        Event::listen(function (NotificationFailed $event) {
            Log::warning('Web push gagal terkirim', [
                'user_id' => $event->subscription->subscribable_id,
                'endpoint' => \Illuminate\Support\Str::limit($event->subscription->endpoint, 60),
                'reason' => $event->report->getReason(),
                'expired' => $event->report->isSubscriptionExpired(),
            ]);
        });

        // Driver Socialite "keycloak" didaftarkan oleh Syifa\KeycloakSso\KeycloakSsoServiceProvider.
        // Aturan sama seperti login lokal (Livewire\Auth\Login): pendaftar mandiri harus
        // disetujui admin dulu — dicek di sini (bukan closure di config, supaya config:cache aman).
        Event::listen(function (KeycloakLoginSucceeded $event) {
            if ($event->user->isApproved() || $event->user->isAdmin()) {
                return;
            }

            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            throw new HttpResponseException(redirect()->route('login')->withErrors([
                'email' => 'Akun kamu belum disetujui oleh admin. Silakan tunggu konfirmasi.',
            ]));
        });
    }
}
