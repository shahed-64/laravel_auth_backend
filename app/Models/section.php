<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class section extends Model
{
    //
         protected $fillable = [
        'section_name',

    ];


    public function students() {
    return $this->hasMany(Student::class);
}
}
