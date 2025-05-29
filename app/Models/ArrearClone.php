<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArrearClone extends model
{
    use HasFactory;

    protected $fillable = [
        'arrear_id',
        'pensioner_id',
        'from_month',
        'to_month',
        'payment_month',
        'basic_arrear',
        'additional_arrear',
        'dr_percentage',
        'dr_arrear',
        'total_arrear',
        'remarks',
        'created_at',
        'updated_at',
        'added_by',
        'edited_by'
    ];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'name', 'role_id');
    }
    public function pensioner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PensionerInformation::class, 'pensioner_id')->select('id', 'name');
    }
}
