<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'amount', 'month', 'year'])]
class Budget extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    public function usedAmount(): string
    {
        return (string) BigDecimal::of((string) ($this->used_amount ?? 0))->toScale(2);
    }

    public function remainingAmount(): string
    {
        return (string) BigDecimal::of((string) $this->amount)
            ->minus($this->usedAmount())->toScale(2);
    }

    public function utilizationPercentage(): float
    {
        $amount = BigDecimal::of((string) $this->amount);
        if ($amount->isZero()) {
            return 0.0;
        }

        return BigDecimal::of($this->usedAmount())
            ->dividedBy($amount, 6, RoundingMode::HalfUp)
            ->multipliedBy(100)->toFloat();
    }

    public function isOverBudget(): bool
    {
        return BigDecimal::of($this->usedAmount())->isGreaterThan((string) $this->amount);
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'month' => 'integer', 'year' => 'integer'];
    }
}
