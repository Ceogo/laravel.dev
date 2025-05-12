<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'group_id',
        'learning_outcome_id',
        'day',
        'pair_number',
        'type',
        'week',
        'semester',
    ];
    public function learningOutcome()
    {
        return $this->belongsTo(LearningOutcome::class);
    }
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function cabinet()
    {
        return $this->belongsTo(Cabinet::class, 'cabinet_id');
    }
}
