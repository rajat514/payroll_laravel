<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaySlipClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_slip_id',
        'net_salary_id',
        'net_salary_clone_id',
        'pay_structure_id',
        'basic_pay',
        'da_rate_id',
        'da_amount',
        'hra_rate_id',
        'hra_amount',
        'npa_rate_id',
        'npa_amount',
        'transport_rate_id',
        'transport_amount',
        'uniform_rate_id',
        'uniform_rate_amount',
        'pay_plus_npa',
        'govt_contribution',
        'da_on_ta',
        'arrears',
        'spacial_pay',
        'da_1',
        'da_2',
        'itc_leave_salary',
        'total_pay',
        'added_by',
        'edited_by',
    ];

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function netSalary(): BelongsTo
    {
        return $this->belongsTo(NetSalary::class, 'net_salary_id', 'id');
    }

    function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function netSalaryClone()
    {
        return $this->belongsTo(NetSalaryClone::class, 'net_salary_clone_id');
    }

    public function salaryArrears()
    {
        return $this->hasMany(SalaryArrearClone::class, 'pay_slip_clone_id', 'id');
    }
}
