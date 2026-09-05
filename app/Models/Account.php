<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'type', 'opening_balance', 'currency', 'icon', 'color', 'is_active'])]
class Account extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'cash' => 'Tunai',
        'bank' => 'Bank',
        'ewallet' => 'Dompet Digital',
        'savings' => 'Tabungan',
        'credit_card' => 'Kartu Kredit',
        'other' => 'Lainnya',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    public function debtPayments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function savingGoalTransactions(): HasMany
    {
        return $this->hasMany(SavingGoalTransaction::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
