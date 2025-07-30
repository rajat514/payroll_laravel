<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryArrearClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_arrear_id',
        'net_salary_clone_id',
        'pay_slip_clone_id',
        'pay_slip_id',
        'type',
        'amount',
        'added_by',
        'edited_by'
    ];
}
