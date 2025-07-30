<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'pensioner_id',
        'bank_name',
        'branch_name',
        'account_no',
        'ifsc_code',
        'is_active',
        'added_by',
        'edited_by',
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
        return $this->belongsTo(\App\Models\PensionerInformation::class, 'pensioner_id');
    }
    function history(): HasMany
    {
        return $this->hasMany(BankAccountClone::class)->orderBy('created_at', 'DESC');
    }
}
