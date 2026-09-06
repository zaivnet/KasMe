<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $this->ensureRegistrationIsOpen();

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureRegistrationIsOpen();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $hasOwner = User::query()->where('is_instance_owner', true)->lockForUpdate()->exists();
            $isFirst = ! $hasOwner && User::query()->lockForUpdate()->count() === 0;

            $user = new User($validated);
            $user->is_instance_owner = $isFirst;
            $user->save();

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('app.home');
    }

    private function ensureRegistrationIsOpen(): void
    {
        if (User::query()->count() === 0) {
            return;
        }

        if (! config('kasme.allow_registration', false)) {
            abort(403, 'Pendaftaran pengguna baru telah dinonaktifkan oleh pemilik instalasi.');
        }
    }
}
