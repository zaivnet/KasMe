<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['account_id', 'type', 'amount', 'transaction_date', 'notes'])]
class SavingGoalTransaction extends Model
{
    public const TYPES = ['contribution' => 'Kontribusi', 'withdrawal' => 'Penarikan'];

    public function savingGoal(): BelongsTo
    {
        return $this->belongsTo(SavingGoal::class)->withTrashed();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'transaction_date' => 'date'];
    }
}
