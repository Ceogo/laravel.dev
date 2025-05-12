<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYearDetail extends Model
{
    protected $fillable = [
        'learning_outcome_id', 'total_hours', 'theoretical_hours',
        'lab_practical_hours', 'course_works', 'professional_training'
    ];

    public function learningOutcome()
    {
        return $this->belongsTo(LearningOutcome::class);
    }
}
