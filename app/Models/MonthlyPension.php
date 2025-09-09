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
        'pension_rel_info_id',
        'net_pension_id',
        'basic_pension',
        'additional_pension',
        'dr_id',
        'dr_amount',
        'medical_allowance',
        'total_pension',
        'remarks',
        'status',
        'arrears',
        'added_by',
        'edited_by'
    ];

    public function getArrearsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function dearness()
    {
        return $this->belongsTo(\App\Models\DearnessRelief::class, 'dr_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(MonthlyPensionClone::class, 'monthly_pension_id')->orderBy('created_at', 'DESC');
    }

    public function pensionRelatedInfo(): BelongsTo
    {
        return $this->belongsTo(PensionRelatedInfo::class, 'pension_rel_info_id');
    }
    public function netPension(): BelongsTo
    {
        return $this->belongsTo(NetPension::class, 'net_pension_id');
    }
}
