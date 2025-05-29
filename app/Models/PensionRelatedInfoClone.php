<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PensionRelatedInfoClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'pension_rel_info_id',
        'pensioner_id',
        'basic_pension',
        'commutation_amount',
        'effective_from',
        'effective_till',
        'is_active',
        'additional_pension',
        'medical_allowance',
        'arrear_id',
        'total_arrear',
        'remarks',
        'added_by',
        'edited_by',
    ];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'name', 'role_id');
    }
}
