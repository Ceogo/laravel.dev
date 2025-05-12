<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'specialty_code', 'specialty_name', 'students_count'];

    public function modules()
    {
        return $this->hasMany(Module::class);
    }
}
