<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\Events\NotificationFailed;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

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

        // Daftarkan driver Socialite "keycloak" (Laravel 12 tanpa EventServiceProvider default).
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
        });
    }
}
