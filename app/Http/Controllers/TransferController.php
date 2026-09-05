<?php

namespace App\Http\Controllers;

use App\Actions\Transfers\CreateTransfer;
use App\Actions\Transfers\DeleteTransfer;
use App\Actions\Transfers\UpdateTransfer;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateTransferRequest;
use App\Models\Transfer;
use App\Services\AccountBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function index(Request $request): View
    {
        $transfers = $request->user()->transfers()->with(['fromAccount', 'toAccount'])
            ->orderByDesc('transfer_date')->orderByDesc('id')->paginate(20);

        return view('transfers.index', compact('transfers'));
    }

    public function create(Request $request, AccountBalanceService $balanceService): View
    {
        Gate::authorize('create', Transfer::class);

        $accounts = $request->user()->accounts()->orderBy('name')->get();

        return view('transfers.create', ['accounts' => $accounts, 'balances' => $balanceService->calculateMany($accounts)]);
    }

    public function store(StoreTransferRequest $request, CreateTransfer $action): RedirectResponse
    {
        $transfer = $action->handle($request->user(), $request->validated());

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer berhasil dibuat.');
    }

    public function show(Transfer $transfer): View
    {
        Gate::authorize('view', $transfer);

        return view('transfers.show', compact('transfer'));
    }

    public function edit(Request $request, Transfer $transfer, AccountBalanceService $balanceService): View
    {
        Gate::authorize('update', $transfer);

        $accounts = $request->user()->accounts()->orderBy('name')->get();

        return view('transfers.edit', ['transfer' => $transfer, 'accounts' => $accounts, 'balances' => $balanceService->calculateMany($accounts)]);
    }

    public function update(UpdateTransferRequest $request, Transfer $transfer, UpdateTransfer $action): RedirectResponse
    {
        $action->handle($transfer, $request->validated());

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer berhasil diperbarui.');
    }

    public function destroy(Transfer $transfer, DeleteTransfer $action): RedirectResponse
    {
        Gate::authorize('delete', $transfer);
        $action->handle($transfer);

        return redirect()->route('transfers.index')->with('success', 'Transfer berhasil dibatalkan.');
    }
}
