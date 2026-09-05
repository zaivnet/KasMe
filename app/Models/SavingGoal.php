<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'target_amount', 'target_date', 'description', 'status'])]
class SavingGoal extends Model
{
    use SoftDeletes;

    public const STATUSES = ['active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingGoalTransaction::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    public function scopeWithProgress(Builder $query): Builder
    {
        return $query->withSum(['transactions as contributions_sum' => fn ($query) => $query->where('type', 'contribution')], 'amount')
            ->withSum(['transactions as withdrawals_sum' => fn ($query) => $query->where('type', 'withdrawal')], 'amount');
    }

    public function progressAmount(): string
    {
        return (string) BigDecimal::of((string) ($this->contributions_sum ?? 0))
            ->minus((string) ($this->withdrawals_sum ?? 0))->toScale(2);
    }

    public function progressPercentage(): float
    {
        return BigDecimal::of($this->progressAmount())->dividedBy((string) $this->target_amount, 6, RoundingMode::HalfUp)
            ->multipliedBy(100)->toFloat();
    }

    public function effectiveStatus(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        return BigDecimal::of($this->progressAmount())->isGreaterThanOrEqualTo((string) $this->target_amount)
            ? 'completed' : 'active';
    }

    protected function casts(): array
    {
        return ['target_amount' => 'decimal:2', 'target_date' => 'date'];
    }
}
