<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NetSalaryClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'net_salary_id',
        'employee_id',
        'month',
        'year',
        'processing_date',
        'net_amount',
        'payment_date',
        'employee_bank_id',
        'is_verified',
        'verified_by',
        'added_by',
        'edited_by',
        'salary_processing_status',
        'salary_processing_date',
        'ddo_status',
        'ddo_date',
        'section_officer_status',
        'section_officer_date',
        'account_officer_status',
        'account_officer_date',

        // Additional earning fields
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
        'salary_arrears',

        // Deduction fields
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
        'deduction_recoveries'
    ];

    public function getSalaryArrearsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getDeductionRecoveriesAttribute($value)
    {
        return json_decode($value, true);
    }

    // function paySlip(): HasOne
    // {
    //     return $this->hasOne(PaySlip::class, 'net_salary_id', 'id');
    // }

    function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // function deduction(): HasOne
    // {
    //     return $this->hasOne(Deduction::class, 'net_salary_id', 'id');
    // }

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function paySlip()
    {
        return $this->hasMany(PaySlipClone::class, 'net_salary_clone_id', 'id');
    }

    public function deduction()
    {
        return $this->hasMany(DeductionClone::class, 'net_salary_clone_id', 'id');
    }
}
