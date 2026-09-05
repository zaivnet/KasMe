<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->boolean('backup_schedule_enabled')->default(false)->after('theme');
            $table->string('backup_schedule_frequency', 20)->default('daily')->after('backup_schedule_enabled');
            $table->string('backup_schedule_time', 5)->default('02:00')->after('backup_schedule_frequency');
            $table->unsignedInteger('backup_retention')->default(7)->after('backup_schedule_time');
            $table->timestamp('last_backup_at')->nullable()->after('backup_retention');
            $table->string('last_backup_status', 30)->nullable()->after('last_backup_at');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'backup_schedule_enabled',
                'backup_schedule_frequency',
                'backup_schedule_time',
                'backup_retention',
                'last_backup_at',
                'last_backup_status',
            ]);
        });
    }
};
