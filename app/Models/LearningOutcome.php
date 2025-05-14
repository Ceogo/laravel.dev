<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningOutcome extends Model
{
    protected $fillable = ['module_id', 'index', 'discipline_name', 'teacher_name'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function semesterDistribution()
    {
        return $this->hasOne(SemesterDistribution::class);
    }

    public function rupDetail()
    {
        return $this->hasOne(RupDetail::class);
    }

    public function academicYearDetail()
    {
        return $this->hasOne(AcademicYearDetail::class);
    }

    public function semesterDetails()
    {
        return $this->hasMany(SemesterDetail::class);
    }

    public function yearTotal()
    {
        return $this->hasOne(YearTotal::class);
    }
    public function cabinets()
    {
        return $this->belongsToMany(Cabinet::class, 'cabinet_learning_outcome');
    }
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'learning_outcome_teacher');
    }
    public function getDuplicates()
    {
        return LearningOutcome::where('index', $this->index)
            ->where('id', '<>', $this->id)
            ->where('discipline_name', '<>', $this->discipline_name)
            ->get();
    }
}
