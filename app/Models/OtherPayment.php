<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherPayment extends Model
{
    protected $fillable = [

        'student_id',

        'item_name',

        'quantity',

        'price',

        'total_amount',

        'payment_method',

        'payment_date',

        'remarks',

    ];


    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
