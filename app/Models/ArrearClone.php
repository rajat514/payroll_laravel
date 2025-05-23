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

    function addby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    function editby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id');
    }
}
