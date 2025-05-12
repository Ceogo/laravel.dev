<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade')->index();
            $table->string('index')->index();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('learning_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->string('index');
            $table->string('discipline_name');
            $table->string('teacher_name')->nullable();
            $table->timestamps();
        });

        Schema::create('learning_outcome_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unique(['learning_outcome_id', 'user_id']);
        });

        Schema::create('semester_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained()->onDelete('cascade');

            $table->json('exams')->nullable();
            $table->json('credits')->nullable();
            $table->json('course_works')->nullable();
            $table->json('control_works')->nullable();

            $table->timestamps();
        });

        Schema::create('rup_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained()->onDelete('cascade');
            $table->float('credits')->nullable();
            $table->integer('total_hours')->nullable();
            $table->integer('theoretical_hours')->nullable();
            $table->integer('lab_practical_hours')->nullable();
            $table->integer('course_works')->nullable();
            $table->integer('professional_practice')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_year_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained()->onDelete('cascade');
            $table->integer('total_hours')->nullable();
            $table->integer('theoretical_hours')->nullable();
            $table->integer('lab_practical_hours')->nullable();
            $table->integer('course_works')->nullable();
            $table->integer('professional_training')->nullable();
            $table->timestamps();
        });

        Schema::create('semester_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            $table->integer('semester_number')->index();
            $table->integer('weeks_count')->nullable();
            $table->integer('hours_per_week')->nullable();
            $table->integer('total_hours')->nullable();
            $table->integer('theoretical_hours')->nullable();
            $table->integer('lab_practical_hours')->nullable();
            $table->integer('course_projects')->nullable();
            $table->integer('project_verification')->nullable();
            $table->integer('professional_training')->nullable();
            $table->integer('lab_practical_duplication')->nullable();
            $table->integer('project_duplication')->nullable();
            $table->integer('verification_duplication')->nullable();
            $table->integer('consultations')->nullable();
            $table->integer('exams')->nullable();
            $table->integer('semester_total')->nullable();
            $table->integer('remaining_hours')->nullable();
            $table->timestamps();

            $table->index(['learning_outcome_id', 'semester_number']);
        });

        Schema::create('year_totals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            $table->integer('total_hours')->nullable(); // 9. Итого за год
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('year_totals');
        Schema::dropIfExists('semester_details');
        Schema::dropIfExists('academic_year_details');
        Schema::dropIfExists('rup_details');
        Schema::dropIfExists('semester_distributions');
        Schema::dropIfExists('learning_outcomes');
        Schema::dropIfExists('modules');
    }
};
