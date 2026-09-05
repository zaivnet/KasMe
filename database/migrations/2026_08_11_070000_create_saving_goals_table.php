<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_goals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('name', 150);
            $table->decimal('target_amount', 18, 2);
            $table->date('target_date')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'target_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_goals');
    }
};
