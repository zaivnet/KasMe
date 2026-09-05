<?php

namespace App\Actions\Transactions;

use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateTransaction
{
    public function handle(Transaction $transaction, array $data): Transaction
    {
        $attachment = Arr::pull($data, 'attachment');
        $removeAttachment = (bool) Arr::pull($data, 'remove_attachment', false);
        $oldPath = $transaction->attachment;
        $newPath = null;

        if ($attachment instanceof UploadedFile) {
            $newPath = $attachment->storeAs(
                "transactions/{$transaction->user_id}",
                Str::uuid().'.'.strtolower($attachment->extension()),
                'local'
            );
            $data['attachment'] = $newPath;
        } elseif ($removeAttachment) {
            $data['attachment'] = null;
        }

        try {
            $transaction->update($data);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }

        if ($oldPath && ($newPath || $removeAttachment)) {
            Storage::disk('local')->delete($oldPath);
        }

        return $transaction;
    }
}
