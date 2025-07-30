<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyPensionClone extends Model
{
    use HasFactory;


    protected $fillable = [
        'monthly_pension_id',
        'pension_rel_info_id',
        'net_pension_id',
        'net_pension_clone_id',
        'basic_pension',
        'additional_pension',
        'dr_id',
        'dr_amount',
        'medical_allowance',
        'total_pension',
        'remarks',
        'status',
        'added_by',
        'edited_by'
    ];


    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function pensioner(): BelongsTo
    {
        return $this->belongsTo(PensionerInformation::class);
    }

    public function dearness(): BelongsTo
    {
        return $this->belongsTo(DearnessRelief::class, 'dr_id')->select('id', 'dr_percentage');
    }

    public function dedcution()
    {
        return $this->hasMany(\App\Models\PensionDeduction::class);
    }

    public function netPension(): BelongsTo
    {
        return $this->belongsTo(NetPension::class)->select('id', 'net_pension', 'pensioner_id');
    }

    public function netPensionClone(): BelongsTo
    {
        return $this->belongsTo(NetPensionClone::class, 'net_pension_clone_id');
    }
}
