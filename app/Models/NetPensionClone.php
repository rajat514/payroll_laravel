<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NetPensionClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'net_pension_id',
        'pensioner_id',
        'pensioner_bank_id',
        'month',
        'year',
        'net_pension',
        'processing_date',
        'payment_date',
        'is_verified',
        'verified_by',
        'added_by',
        'edited_by',
        'pensioner_operator_status',
        'pensioner_operator_date',
        'ddo_status',
        'ddo_date',
        'section_officer_status',
        'section_officer_date',
        'account_officer_status',
        'account_officer_date',

        'pension_rel_info_id',
        'net_pension_clone_id',
        'basic_pension',
        'additional_pension',
        'dr_id',
        'dr_amount',
        'medical_allowance',
        'total_pension',
        'remarks',
        'status',

        'commutation_amount',
        'income_tax',
        'recovery',
        'other',
        'amount',
        'description',
    ];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function monthlyPension(): HasMany
    {
        return $this->hasMany(MonthlyPensionClone::class, 'net_pension_clone_id', 'id');
    }

    public function pensionerDeduction(): HasMany
    {
        return $this->hasMany(PensionDeductionClone::class, 'net_pension_clone_id', 'id');
    }

    public function pensioner(): BelongsTo
    {
        return $this->belongsTo(PensionerInformation::class)->select('id', 'name', 'ppo_no');
    }

    public function netPension(): BelongsTo
    {
        return $this->belongsTo(NetPension::class)->select('id', 'net_pension');
    }
}
