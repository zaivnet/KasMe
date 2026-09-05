<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateTransaction
{
    public function handle(User $user, array $data): Transaction
    {
        $attachment = Arr::pull($data, 'attachment');
        Arr::forget($data, 'remove_attachment');

        if ($attachment instanceof UploadedFile) {
            $data['attachment'] = $attachment->storeAs(
                "transactions/{$user->id}",
                Str::uuid().'.'.strtolower($attachment->extension()),
                'local'
            );
        }

        try {
            return $user->transactions()->create($data);
        } catch (\Throwable $exception) {
            if (isset($data['attachment'])) {
                Storage::disk('local')->delete($data['attachment']);
            }
            throw $exception;
        }
    }
}
