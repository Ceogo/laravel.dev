<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YearTotal extends Model
{
    protected $fillable = ['learning_outcome_id', 'total_hours'];

    public function learningOutcome()
    {
        return $this->belongsTo(LearningOutcome::class);
    }
}
