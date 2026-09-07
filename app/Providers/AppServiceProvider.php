<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Backup\ArchiveManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CarbonImmutable::setLocale(config('app.locale'));

        Gate::define('manage-system-backups', function (User $user): bool {
            return (bool) $user->is_instance_owner;
        });
    }
}
