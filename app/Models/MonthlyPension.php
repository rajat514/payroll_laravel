<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyPension extends Model
{
    use HasFactory;

    // protected $primaryKey = 'pension_id';

    // protected $fillable = [
    //     'pension_rel_info_id',
    //     'pensioner_id',
    //     'month',
    //     'basic_pension',
    //     'commutation_amount',
    //     'additional_pension',
    //     'dr_id',
    //     'dr_amount',
    //     'medical_allowance',
    //     'total_pension',
    //     'total_recovery',
    //     'net_pension',
    //     'remarks',
    //     'status'
    // ];


    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function dearness()
    {
        return $this->belongsTo(\App\Models\DearnessRelief::class, 'dr_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(MonthlyPensionClone::class, 'monthly_pension_id');
    }

    public function pensionRelatedInfo(): BelongsTo
    {
        return $this->belongsTo(PensionRelatedInfo::class, 'pension_rel_info_id');
    }
    public function netPension(): BelongsTo
    {
        return $this->belongsTo(NetPension::class);
    }
}
