<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Sama seperti Filament\Http\Middleware\Authenticate, tapi kalau user SUDAH login
 * namun tidak boleh masuk panel (peserta / pembimbing), dia diarahkan ke portal
 * ('home') — bukan dilempar halaman 403 yang membingungkan.
 */
class Authenticate extends FilamentAuthenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return; /** @phpstan-ignore-line */
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Filament::getCurrentOrDefaultPanel();

        $canAccess = $user instanceof FilamentUser
            ? $user->canAccessPanel($panel)
            : (config('app.env') === 'local');

        if (! $canAccess) {
            throw new HttpResponseException(
                redirect()->route('home'),
            );
        }
    }
}
