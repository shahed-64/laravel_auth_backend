<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Teacher extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'designation',
        'department',
        'qualification',
        'phone',
        'email',
        'join_date',
        'salary',
        'image',
        'teacher_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'join_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function attendances()
    {
        return $this->hasMany(TeachersAttendance::class);
    }

    // নতুন শিফট রিলেশনশিপ যোগ করা হলো

    public function shifts(): BelongsToMany
{
    return $this->belongsToMany(Shift::class, 'teacher_shift');
}
}
