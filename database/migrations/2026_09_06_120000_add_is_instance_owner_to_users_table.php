<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_instance_owner')->default(false)->after('password');
        });

        // Safe upgrade strategy for existing installations:
        // If there is exactly one user, safely designate them as the instance owner.
        // If there are multiple users, do not guess; operator can use `php artisan kasme:set-owner`.
        if (User::query()->count() === 1) {
            User::query()->first()->updateQuietly(['is_instance_owner' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_instance_owner');
        });
    }
};
