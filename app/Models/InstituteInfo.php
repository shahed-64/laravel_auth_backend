<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteInfo extends Model
{
    protected $fillable = [
        'institute_name',
        'established_year',
        'location',
        'contact',
        'email',
        'logo',
    ];
}
