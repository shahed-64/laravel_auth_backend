<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * একটি Subject অনেকগুলো Class-এর সাথে থাকতে পারে।
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            ClssM::class,
            'class_subject',
            'subject_id',
            'class_id'
        );
    }
    public function resultSubjects()
{
    return $this->hasMany(ResultSubject::class);
}
public function classGroups(): BelongsToMany
{
    return $this->belongsToMany(
        ClassGroup::class,
        'group_subjects',
        'subject_id',
        'class_group_id'
    );
}
}
