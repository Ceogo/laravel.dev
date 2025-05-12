<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemesterDetail extends Model
{
    protected $fillable = [
        'learning_outcome_id', 'semester_number', 'weeks_count', 'hours_per_week',
        'total_hours', 'theoretical_hours', 'lab_practical_hours', 'course_projects',
        'project_verification', 'professional_training', 'lab_practical_duplication',
        'project_duplication', 'verification_duplication', 'consultations', 'exams',
        'semester_total'
    ];

    public function learningOutcome()
    {
        return $this->belongsTo(LearningOutcome::class);
    }
}
