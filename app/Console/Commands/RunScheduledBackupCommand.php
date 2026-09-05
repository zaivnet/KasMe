<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use App\Services\Backup\BackupService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunScheduledBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:scheduled {--force : Force execution regardless of schedule window}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled backup for users with scheduled backups enabled';

    public function handle(BackupService $backupService): int
    {
        $force = (bool) $this->option('force');
        $now = now();

        $settings = Setting::query()
            ->with('user')
            ->where('backup_schedule_enabled', true)
            ->get();

        if ($settings->isEmpty() && ! $force) {
            $this->info('No users have scheduled backups enabled.');

            return self::SUCCESS;
        }

        // If forced and no settings found, target first user or system
        if ($settings->isEmpty() && $force) {
            $firstUser = User::query()->first();
            $this->info('Force running backup for first available user or system.');
            try {
                $filename = $backupService->createBackup('scheduled', $firstUser);
                $this->info("Backup created: {$filename}");

                return self::SUCCESS;
            } catch (Throwable $e) {
                $this->error("Backup failed: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        foreach ($settings as $setting) {
            $user = $setting->user;
            if (! $user) {
                continue;
            }

            if (! $force && ! $this->isDue($setting, $now)) {
                continue;
            }

            $this->info("Creating scheduled backup for user: {$user->email}");

            try {
                $filename = $backupService->createBackup('scheduled', $user);
                $this->info("Backup created: {$filename}");

                // Apply retention policy
                $pruned = $backupService->applyRetention($setting->backup_retention ?? 7);
                if ($pruned > 0) {
                    $this->info("Pruned {$pruned} old backup(s) according to retention policy.");
                }

                $setting->update([
                    'last_backup_at' => $now,
                    'last_backup_status' => 'success',
                ]);
            } catch (Throwable $e) {
                $this->error("Scheduled backup failed for {$user->email}: {$e->getMessage()}");
                Log::error("Scheduled backup failed for user {$user->id}: " . $e->getMessage());

                $setting->update([
                    'last_backup_status' => 'failed',
                ]);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Check if the scheduled backup is due and not already run in the current window.
     */
    public function isDue(Setting $setting, Carbon $now): bool
    {
        $frequency = $setting->backup_schedule_frequency ?: 'daily';
        $scheduledTime = $setting->backup_schedule_time ?: '02:00';
        [$hour, $minute] = array_map('intval', explode(':', $scheduledTime . ':00'));

        // Check if current hour and minute match the scheduled time
        // (or within a 15-minute grace window if cron runs every 5-15 mins)
        $scheduledDateTime = $now->copy()->setTime($hour, $minute, 0);

        // If current time is before the scheduled time on this day, it's not time yet
        if ($now->lt($scheduledDateTime)) {
            return false;
        }

        $lastBackup = $setting->last_backup_at;

        // Idempotency: verify it hasn't already run in the current window
        if ($lastBackup) {
            $lastRun = Carbon::parse($lastBackup);

            switch ($frequency) {
                case 'daily':
                    // Already ran today
                    if ($lastRun->isSameDay($now)) {
                        return false;
                    }
                    break;

                case 'weekly':
                    // Only run on Sunday (dayOfWeek === 0)
                    if ($now->dayOfWeek !== 0) {
                        return false;
                    }
                    // Already ran this week
                    if ($lastRun->isSameWeek($now)) {
                        return false;
                    }
                    break;

                case 'monthly':
                    // Only run on the 1st of the month
                    if ($now->day !== 1) {
                        return false;
                    }
                    // Already ran this month
                    if ($lastRun->isSameMonth($now)) {
                        return false;
                    }
                    break;
            }
        } else {
            // For weekly/monthly without previous backup, check day of week/month
            if ($frequency === 'weekly' && $now->dayOfWeek !== 0) {
                return false;
            }
            if ($frequency === 'monthly' && $now->day !== 1) {
                return false;
            }
        }

        return true;
    }
}
