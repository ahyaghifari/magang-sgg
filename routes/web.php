<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Home;
use App\Livewire\Journals\Index as JournalIndex;
use App\Livewire\Pembimbing\Activities as PembimbingActivities;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'home' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', Home::class)->name('home');

    Route::get('/jurnal', JournalIndex::class)->name('journals.index');

    Route::get('/kegiatan', PembimbingActivities::class)->name('pembimbing.activities');

    Route::post('/logout', function () {
        $wasSso = request()->session()->get('sso');

        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // Single logout: akhiri juga sesi Keycloak supaya login SSO berikutnya minta prompt.
        if ($wasSso && config('services.keycloak.base_url')) {
            $base = rtrim((string) config('services.keycloak.base_url'), '/');
            $realm = config('services.keycloak.realms');

            return redirect($base.'/realms/'.$realm.'/protocol/openid-connect/logout?'.http_build_query([
                'client_id' => config('services.keycloak.client_id'),
                'post_logout_redirect_uri' => route('login'),
            ]));
        }

        return redirect()->route('login');
    })->name('logout');
});
