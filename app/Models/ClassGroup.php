<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassGroup extends Model
{
    protected $fillable = [
        'group_name',
    ];

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'group_subjects',
            'class_group_id',
            'subject_id'
        );
    }
}
