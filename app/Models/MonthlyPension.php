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

    protected $fillable = [
        'pensioner_id',
        'month',
        'basic_pension',
        'commutation_amount',
        'additional_pension',
        'dr_id',
        'dr_amount',
        'medical_allowance',
        'total_pension',
        'total_recovery',
        'net_pension',
        'remarks',
        'status'
    ];


    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'name', 'role_id');
    }

    public function pensioner()
    {
        return $this->belongsTo(\App\Models\PensionerInformation::class);
    }

    public function dr()
    {
        return $this->belongsTo(\App\Models\DearnessRelief::class);
    }

    public function dedcution()
    {
        return $this->hasMany(\App\Models\PensionDeduction::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(MonthlyPensionClone::class, 'monthly_pension_id');
    }
}
