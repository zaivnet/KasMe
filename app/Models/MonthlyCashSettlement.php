<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'period',
    'period_balance_snapshot',
    'settled_amount',
    'settled_at',
    'notes',
    'voided_at',
    'void_reason',
])]
class MonthlyCashSettlement extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'period_balance_snapshot' => 'decimal:2',
            'settled_amount' => 'decimal:2',
            'settled_at' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isActive(): bool
    {
        return $this->voided_at === null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('voided_at');
    }

    public function scopeVoided($query)
    {
        return $query->whereNotNull('voided_at');
    }

    public function discrepancy(): BigDecimal
    {
        return BigDecimal::of((string) $this->period_balance_snapshot)
            ->minus((string) $this->settled_amount)
            ->toScale(2);
    }

    public function hasDiscrepancy(): bool
    {
        return ! $this->discrepancy()->isZero();
    }

    public function periodLabel(): string
    {
        if ($this->period instanceof CarbonInterface) {
            return $this->period->locale('id')->translatedFormat('F Y');
        }

        return (string) $this->period;
    }
}
