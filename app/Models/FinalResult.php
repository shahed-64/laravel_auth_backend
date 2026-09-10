<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalResult extends Model
{
    protected $fillable = [
        'examination_id',
        'percentage',
    ];

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }
}
