<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_goal_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('saving_goal_id')->constrained();
            $table->foreignId('account_id')->constrained();
            $table->string('type', 20);
            $table->decimal('amount', 18, 2);
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['saving_goal_id', 'transaction_date']);
            $table->index(['account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_goal_transactions');
    }
};
