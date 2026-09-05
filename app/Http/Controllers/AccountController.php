<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request, AccountBalanceService $balanceService): View
    {
        $accounts = $request->user()->accounts()->latest()->paginate(12);
        $balances = $balanceService->calculateMany($accounts->getCollection());

        return view('accounts.index', compact('accounts', 'balances'));
    }

    public function create(): View
    {
        Gate::authorize('create', Account::class);

        return view('accounts.create', ['types' => Account::TYPES]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = $request->user()->accounts()->create($request->validated());

        return redirect()->route('accounts.show', $account)->with('success', 'Akun berhasil dibuat.');
    }

    public function show(Account $account, AccountBalanceService $balanceService): View
    {
        Gate::authorize('view', $account);

        return view('accounts.show', [
            'account' => $account,
            'balance' => $balanceService->calculate($account),
        ]);
    }

    public function edit(Account $account): View
    {
        Gate::authorize('update', $account);

        return view('accounts.edit', ['account' => $account, 'types' => Account::TYPES]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update($request->validated());

        return redirect()->route('accounts.show', $account)->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        Gate::authorize('delete', $account);
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Akun berhasil diarsipkan.');
    }
}
