<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['category_id', 'name', 'amount', 'due_date', 'recurrence', 'status', 'notes'])]
class Bill extends Model
{
    use SoftDeletes;

    public const RECURRENCES = ['none' => 'Tidak berulang', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'];

    public const STATUSES = ['unpaid' => 'Belum Dibayar', 'paid' => 'Lunas', 'overdue' => 'Terlambat'];

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

    public function effectiveStatus(): string
    {
        if ($this->status === 'paid') {
            return 'paid';
        }

        $today = CarbonImmutable::today(config('app.timezone'));

        return $this->due_date->lt($today) ? 'overdue' : $this->status;
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_date' => 'date'];
    }
}
