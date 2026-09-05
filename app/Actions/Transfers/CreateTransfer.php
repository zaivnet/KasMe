<?php

namespace App\Actions\Transfers;

use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTransfer
{
    public function handle(User $user, array $data): Transfer
    {
        return DB::transaction(function () use ($user, $data): Transfer {
            $count = Account::ownedBy($user)->whereKey([$data['from_account_id'], $data['to_account_id']])
                ->lockForUpdate()->get()->count();
            if ($count !== 2) {
                throw ValidationException::withMessages(['from_account_id' => 'Kedua akun harus milik Anda dan tersedia.']);
            }

            return $user->transfers()->create($data);
        });
    }
}
