<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->decimal('amount', 18, 2);
            $table->date('due_date');
            $table->string('recurrence', 20);
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'due_date']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
