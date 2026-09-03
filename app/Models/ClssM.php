<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClssM extends Model
{
    protected $guarded = [];

    /**
     * একটি ক্লাসের অনেক Student থাকতে পারে।
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * একটি ক্লাসে অনেক Subject থাকতে পারে
     * এবং একটি Subject অনেক Class-এ থাকতে পারে।
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'class_subject',
            'class_id',
            'subject_id'
        );
    }
}
