<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['account_id', 'category_id', 'type', 'adjustment_direction', 'amount', 'transaction_date', 'description', 'attachment'])]
class Transaction extends Model
{
    use SoftDeletes;

    public const TYPES = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran', 'adjustment' => 'Penyesuaian'];

    public const ADJUSTMENT_DIRECTIONS = ['increase' => 'Penambahan', 'decrease' => 'Pengurangan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'transaction_date' => 'date'];
    }
}
