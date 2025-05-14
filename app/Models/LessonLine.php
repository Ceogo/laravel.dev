<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonLine extends Model
{
    protected $fillable = [
        'learning_outcome_id',
        'group_id',
        'target_week',
        'is_processed',
    ];
    public function learningOutcome()
    {
        return $this->belongsTo(LearningOutcome::class);
    }
}
