<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 19.5.2 — Settlement Audit Integrity
 *
 * Removes the UNIQUE(user_id, period) constraint from monthly_cash_settlements.
 *
 * Rationale:
 *   The previous constraint forced settle() to reactivate/overwrite voided rows,
 *   destroying the audit trail (voided_at, void_reason, original settled_amount).
 *
 *   With the constraint removed, a new INSERT can always be made for the same
 *   (user_id, period) after a void, preserving both records independently.
 *   Application-level enforcement (DB transaction + lockForUpdate) ensures
 *   no two ACTIVE settlements exist for the same (user_id, period).
 *
 * Compatibility: MySQL 8+, MariaDB 10.5+, SQLite.
 * Avoids partial/filtered unique indexes which are NOT portable across drivers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_cash_settlements', function (Blueprint $table): void {
            // Drop the unique constraint — name follows Laravel's convention:
            // {table}_{columns}_unique
            $table->dropUnique(['user_id', 'period']);
        });
    }

    public function down(): void
    {
        // NOTE: Re-adding the unique constraint in down() is only safe on a
        // clean/test database. In production rollback would fail if multiple
        // rows exist for the same (user_id, period), which is the exact
        // scenario this migration enables. The down() is provided for
        // test/development rollback completeness only.
        Schema::table('monthly_cash_settlements', function (Blueprint $table): void {
            $table->unique(['user_id', 'period']);
        });
    }
};
