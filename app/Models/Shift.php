<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shift extends Model
{
    protected $fillable = ['name', 'start_time'];
// tEACHER rELATION
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_shift');
    }
//sTAFF rELATION
    public function staffs()
    {
        return $this->hasMany(Staff::class);
    }
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
