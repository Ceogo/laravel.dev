<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RupDetail extends Model
{
    protected $fillable = [
        'learning_outcome_id', 'credits', 'total_hours', 'theoretical_hours',
        'lab_practical_hours', 'course_works', 'professional_practice'
    ];

    public function learningOutcome()
    {
        return $this->belongsTo(LearningOutcome::class);
    }
}
