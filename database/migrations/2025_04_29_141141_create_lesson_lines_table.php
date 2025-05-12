<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lesson_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->integer('target_week')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();

            // Уникальный индекс для group_id + target_week
            $table->unique(['group_id', 'target_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_lines');
    }
};
