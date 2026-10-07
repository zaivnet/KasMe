<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->date('cash_accountability_start_period')->nullable()->after('theme');
        });

        Schema::table('monthly_cash_settlements', function (Blueprint $table): void {
            $table->timestamp('voided_at')->nullable()->after('notes');
            $table->text('void_reason')->nullable()->after('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn('cash_accountability_start_period');
        });

        Schema::table('monthly_cash_settlements', function (Blueprint $table): void {
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
