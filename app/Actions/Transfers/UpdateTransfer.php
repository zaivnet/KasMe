<?php

namespace App\Actions\Transfers;

use App\Models\Account;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateTransfer
{
    public function handle(Transfer $transfer, array $data): Transfer
    {
        return DB::transaction(function () use ($transfer, $data): Transfer {
            $locked = Transfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $count = Account::ownedBy($transfer->user)->whereKey([$data['from_account_id'], $data['to_account_id']])
                ->lockForUpdate()->get()->count();
            if ($count !== 2) {
                throw ValidationException::withMessages(['from_account_id' => 'Kedua akun harus milik Anda dan tersedia.']);
            }
            $locked->update($data);

            return $locked;
        });
    }
}
