<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabinet extends Model
{
    protected $fillable = [
        'number', 'description', 'capacity',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'cabinet_id');
    }
    public function learningOutcomes()
    {
        return $this->belongsToMany(LearningOutcome::class);
    }

    public function getAvailableCapacity($groupId)
    {
        $groupSize = Group::find($groupId)->size;
        return $this->capacity >= $groupSize;
    }
    public function preferredTeachers()
    {
        return $this->belongsToMany(User::class, 'teacher_cabinet');
    }
}
