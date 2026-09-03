<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    //
    protected $fillable = [
    'expense_type',
    'employee_name',
    'salary_amount',
    'paid_amount',
    'due_amount',
    'payment_method',
    'payment_date',
    'payment_month',
    'created_by'
];
}
