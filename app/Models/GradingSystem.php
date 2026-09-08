<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradingSystem extends Model
{
    protected $fillable = [
        'grade',
        'grade_point',
        'min_percentage',
    ];

    protected $casts = [
        'grade_point' => 'decimal:2',
        'min_percentage' => 'decimal:2',
    ];
}
