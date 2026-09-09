<?php

use App\Http\Controllers\Auth\KeycloakController;
use Illuminate\Support\Facades\Route;

// Butuh session + CSRF state untuk alur OAuth → middleware 'web'.
// Di LUAR 'auth' supaya tamu bisa memulai login.
Route::middleware('web')->group(function () {
    Route::get('/auth/keycloak/redirect', [KeycloakController::class, 'redirect'])->name('sso.redirect');
    Route::get('/auth/keycloak/callback', [KeycloakController::class, 'callback'])->name('sso.callback');
});
