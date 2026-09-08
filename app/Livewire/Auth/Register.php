<?php

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.app')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $institusi_id = '';

    public string $jenis_kelamin = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'institusi_id' => ['required', 'integer', 'exists:institutions,id'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nama',
            'institusi_id' => 'institusi',
            'jenis_kelamin' => 'jenis kelamin',
            'password' => 'kata sandi',
        ];
    }

    public function register()
    {
        $this->ensureIsNotRateLimited();

        $data = $this->validate();

        RateLimiter::hit($this->throttleKey());

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Intern,
                // approved_at sengaja dibiarkan null — menunggu persetujuan admin.
            ]);

            // Beri peran "peserta" bila perannya sudah ada (dikelola lewat Shield).
            if (Role::where('name', 'peserta')->where('guard_name', 'web')->exists()) {
                $user->assignRole('peserta');
            }

            $user->intern()->create([
                'institusi_id' => $data['institusi_id'],
                'nama' => $data['name'],
                'jenis_kelamin' => $data['jenis_kelamin'],
            ]);
        });

        session()->flash('status', 'Pendaftaran berhasil. Akun kamu menunggu persetujuan admin sebelum bisa digunakan.');

        return $this->redirect(route('login'), navigate: true);
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
        return 'register|' . Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.register', [
            'institutions' => Institution::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
