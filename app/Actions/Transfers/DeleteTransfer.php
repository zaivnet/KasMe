<?php

namespace App\Actions\Transfers;

use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class DeleteTransfer
{
    public function handle(Transfer $transfer): void
    {
        DB::transaction(function () use ($transfer): void {
            Transfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail()->delete();
        });
    }
}
