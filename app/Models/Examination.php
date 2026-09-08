<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Examination extends Model
{
    protected $fillable = [
        'examination_type',
        'examination_year',
        'exam_mark',
    ];

    public function examination()
    {
        return $this->hasMany(Result::class);
    }
}
