<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;

class DeleteTransaction
{
    public function handle(Transaction $transaction): void
    {
        $transaction->delete();

        if ($transaction->attachment) {
            Storage::disk('local')->delete($transaction->attachment);
            $transaction->forceFill(['attachment' => null])->saveQuietly();
        }
    }
}
