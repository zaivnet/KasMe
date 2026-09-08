<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of all users (Instance Owner only).
     */
    public function index(): View
    {
        Gate::authorize('manage-users');

        $users = User::query()
            ->withCount([
                'accounts',
                'transactions',
                'transfers',
                'budgets',
                'bills',
                'debts',
                'savingGoals',
            ])
            ->orderByDesc('is_instance_owner')
            ->orderBy('name')
            ->get();

        return view('settings.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        Gate::authorize('manage-users');

        return view('settings.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $newUser = new User();
        $newUser->name = $validated['name'];
        $newUser->email = $validated['email'];
        $newUser->password = Hash::make($validated['password']);
        $newUser->is_instance_owner = false; // Strictly non-owner
        $newUser->is_active = $request->has('is_active') ? $request->boolean('is_active') : true;
        $newUser->save();

        return redirect()->route('settings.users.index')
            ->with('status', "Pengguna {$newUser->name} berhasil ditambahkan.");
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        Gate::authorize('manage-users');

        return view('settings.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Instance Owner must always remain active and owner status cannot be changed
        if (! $user->is_instance_owner) {
            $user->is_active = $request->boolean('is_active');
        }

        $user->save();

        return redirect()->route('settings.users.index')
            ->with('status', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Reset the password for the specified user.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('settings.users.index')
            ->with('status', "Kata sandi pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Remove or deactivate the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        // Prevent deleting or deactivating Instance Owner or self
        if ($user->is_instance_owner || $user->id === auth()->id()) {
            return redirect()->route('settings.users.index')
                ->with('error', 'Akun Instance Owner tidak dapat dihapus atau dinonaktifkan.');
        }

        // Deactivation-first safe delete strategy
        if ($user->hasFinancialData()) {
            $user->is_active = false;
            $user->save();

            return redirect()->route('settings.users.index')
                ->with('status', "Pengguna {$user->name} memiliki riwayat data keuangan. Akun dinonaktifkan demi menjaga integritas data.");
        }

        // Hard delete only when zero financial records exist
        $user->setting()?->delete();
        $user->delete();

        return redirect()->route('settings.users.index')
            ->with('status', "Pengguna {$user->name} berhasil dihapus permanen.");
    }
}
