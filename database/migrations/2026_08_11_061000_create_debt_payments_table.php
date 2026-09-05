<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('debt_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->decimal('amount', 18, 2);
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['debt_id', 'payment_date']);
            $table->index(['account_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
    }
};
