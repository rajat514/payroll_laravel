<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayCommissionClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_commission_id',
        'name',
        'year',
        'is_active',
        'added_by',
        'edited_by'
    ];

    public function PayMatrixLevel(): HasMany
    {
        return $this->hasMany(PayMatrixLevel::class);
    }

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }
}
