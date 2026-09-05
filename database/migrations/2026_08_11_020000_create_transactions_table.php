<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('adjustment_direction', 10)->nullable();
            $table->decimal('amount', 18, 2);
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'transaction_date']);
            $table->index(['account_id', 'transaction_date']);
            $table->index(['category_id', 'transaction_date']);
            $table->index(['type', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
