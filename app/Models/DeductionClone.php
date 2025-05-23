<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeductionClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'deduction_id',
        'net_salary_id',
        'income_tax',
        'professional_tax',
        'license_fee',
        'nfch_donation',
        'gpf',
        'transport_allowance_recovery',
        'hra_recovery',
        'computer_advance',
        'computer_advance_installment',
        'computer_advance_inst_no',
        'computer_advance_balance',
        'employee_contribution_10',
        'govt_contribution_14_recovery',
        'dies_non_recovery',
        'computer_advance_interest',
        'gis',
        'pay_recovery',
        'nps_recovery',
        'lic',
        'credit_society',
        'total_deductions',
        'added_by',
        'edited_by',
    ];

    function netSalary(): BelongsTo
    {
        return $this->belongsTo(NetSalary::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'name', 'role_id');
    }
}
