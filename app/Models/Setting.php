<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'currency',
    'date_format',
    'timezone',
    'theme',
    'backup_schedule_enabled',
    'backup_schedule_frequency',
    'backup_schedule_time',
    'backup_retention',
    'last_backup_at',
    'last_backup_status',
])]
class Setting extends Model
{
    public const THEMES = ['system' => 'Ikuti sistem', 'light' => 'Terang', 'dark' => 'Gelap'];

    public const DATE_FORMATS = [
        'd M Y' => '31 Des 2026',
        'd/m/Y' => '31/12/2026',
        'm/d/Y' => '12/31/2026',
        'Y-m-d' => '2026-12-31',
    ];

    public const BACKUP_FREQUENCIES = [
        'daily' => 'Harian',
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
    ];

    public const BACKUP_RETENTIONS = [
        7 => 'Simpan 7 cadangan terakhir',
        14 => 'Simpan 14 cadangan terakhir',
        30 => 'Simpan 30 cadangan terakhir',
        0 => 'Simpan semua cadangan',
    ];

    protected function casts(): array
    {
        return [
            'backup_schedule_enabled' => 'boolean',
            'backup_retention' => 'integer',
            'last_backup_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formatDate(?CarbonInterface $date): ?string
    {
        return $date?->locale('id')->translatedFormat($this->date_format);
    }
}
