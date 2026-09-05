<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['account_id', 'amount', 'payment_date', 'notes'])]
class DebtPayment extends Model
{
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class)->withTrashed();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }
}
