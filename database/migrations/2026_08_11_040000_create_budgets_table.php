<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->decimal('amount', 18, 2);
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->timestamps();
            $table->unique(['user_id', 'category_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
