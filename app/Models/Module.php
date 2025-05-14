<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['group_id', 'index', 'name'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function learningOutcomes()
    {
        return $this->hasMany(LearningOutcome::class);
    }
    public function getDuplicates()
    {
        return Module::where('index', $this->index)
            ->where('id', '<>', $this->id)
            ->get();
    }
}
