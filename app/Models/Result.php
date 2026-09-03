<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_year',
        'exam_type',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function resultSubjects(): HasMany
    {
        return $this->hasMany(ResultSubject::class);
    }
}
