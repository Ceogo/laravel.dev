<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemesterDistribution extends Model
{
    protected $fillable = ['learning_outcome_id', 'exams', 'credits', 'course_works', 'control_works'];

    protected $casts = [
        'exams' => 'array',
        'credits' => 'array',
        'course_works' => 'array',
        'control_works' => 'array',
    ];

    public static function rules()
    {
        return [
            'exams' => ['nullable', 'array'],
            'credits' => ['nullable', 'array'],
            'course_works' => ['nullable', 'array'],
            'control_works' => ['nullable', 'array'],
        ];
    }

    public function learningOutcome()
    {
        return $this->belongsTo(LearningOutcome::class);
    }
}
