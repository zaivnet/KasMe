<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['parent_id', 'name', 'type', 'icon', 'color', 'is_active'])]
class Category extends Model
{
    public const TYPES = ['income' => 'Pemasukan', 'expense' => 'Pengeluaran'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function isUsed(): bool
    {
        if (isset($this->transactions_count, $this->budgets_count, $this->bills_count, $this->children_count)) {
            return ($this->transactions_count + $this->budgets_count + $this->bills_count + $this->children_count) > 0;
        }

        return $this->transactions()->exists()
            || $this->budgets()->exists()
            || $this->bills()->exists()
            || $this->children()->exists();
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
