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
        Schema::create('cabinets', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->timestamps();

            $table->index('number');
        });

        Schema::create('cabinet_learning_outcome', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained('cabinets')->onDelete('cascade');
            $table->foreignId('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            $table->unique(['cabinet_id', 'learning_outcome_id']);
        });

        Schema::create('teacher_cabinet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('cabinet_id')->constrained('cabinets')->onDelete('cascade');
            $table->unique(['user_id', 'cabinet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabinets');
    }
};
