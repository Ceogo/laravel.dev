<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            $table->string('day');
            $table->integer('pair_number');
            $table->string('type');
            $table->integer('week');
            $table->integer('semester');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cabinet_id')->nullable()->constrained('cabinets')->onDelete('set null');
            $table->timestamps();

            // Комбинированный индекс для частых запросов по группе, семестру и неделе
            $table->index(['group_id', 'semester', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
