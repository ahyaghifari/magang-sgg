<?php

use App\Livewire\Attendance\Index as AttendanceIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Home;
use App\Livewire\Journals\Index as JournalIndex;
use App\Livewire\Leaves\Index as LeaveIndex;
use App\Livewire\Pembimbing\Activities as PembimbingActivities;
use App\Livewire\Pembimbing\Attendance as PembimbingAttendance;
use App\Livewire\Pembimbing\Leaves as PembimbingLeaves;
use App\Livewire\Pembimbing\Tasks as PembimbingTasks;
use App\Livewire\Tasks\Index as TaskIndex;
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

    Route::get('/presensi', AttendanceIndex::class)->name('attendance.index');
    Route::get('/presensi-intern', PembimbingAttendance::class)->name('pembimbing.attendance');

    Route::get('/kegiatan', PembimbingActivities::class)->name('pembimbing.activities');

    Route::get('/tugas', TaskIndex::class)->name('tasks.index');
    Route::get('/tugas-intern', PembimbingTasks::class)->name('pembimbing.tasks');

    Route::get('/izin', LeaveIndex::class)->name('leaves.index');
    Route::get('/izin-intern', PembimbingLeaves::class)->name('pembimbing.leaves');

    // Toggle "lihat sebagai intern" untuk admin/pembimbing yang juga punya data Intern
    // sendiri — dipakai lewat tombol di sidebar portal (components/layouts/app.blade.php).
    Route::post('/portal/lihat-sebagai-intern', function () {
        abort_unless(Auth::user()->canToggleIntern(), 403);

        request()->session()->put(
            'portal_view_as_intern',
            ! request()->session()->get('portal_view_as_intern', false),
        );

        return redirect()->route('home');
    })->name('portal.toggle-intern-view');

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
