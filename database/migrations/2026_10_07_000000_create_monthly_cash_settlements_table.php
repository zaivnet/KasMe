<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_cash_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period');
            $table->decimal('period_balance_snapshot', 18, 2);
            $table->decimal('settled_amount', 18, 2);
            $table->date('settled_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period']);
            $table->index(['user_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_cash_settlements');
    }
};
