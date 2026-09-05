<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type', 20);
            $table->string('person_name', 150);
            $table->decimal('original_amount', 18, 2);
            $table->decimal('remaining_amount', 18, 2);
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
