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
    ];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'name', 'role_id');
    }

    public function monthlyPension(): HasOne
    {
        return $this->hasOne(MonthlyPension::class);
    }

    public function pensionerDeduction(): HasOne
    {
        return $this->hasOne(PensionDeduction::class);
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
