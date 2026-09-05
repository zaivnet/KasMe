<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['type', 'person_name', 'original_amount', 'remaining_amount', 'start_date', 'due_date', 'status', 'notes'])]
class Debt extends Model
{
    use SoftDeletes;

    public const TYPES = ['debt' => 'Utang', 'receivable' => 'Piutang'];

    public const STATUSES = ['active' => 'Aktif', 'paid' => 'Lunas', 'overdue' => 'Terlambat'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereBelongsTo($user);
    }

    public function effectiveStatus(): string
    {
        if ((float) $this->remaining_amount === 0.0) {
            return 'paid';
        }

        return $this->due_date && $this->due_date->lt(CarbonImmutable::today(config('app.timezone')))
            ? 'overdue' : 'active';
    }

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }
}
