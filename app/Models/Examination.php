<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Examination extends Model
{
    //
     protected $fillable = [
        'examination_type',
        'examination_year'
    ];



        public function examination()
    {
        return $this->hasMany(Result::class);
    }
}
