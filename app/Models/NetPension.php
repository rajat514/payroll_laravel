<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NetPension extends Model
{
    use HasFactory;

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function monthlyPension(): HasOne
    {
        return $this->hasOne(MonthlyPension::class);
    }

    public function pensionerDeduction(): HasOne
    {
        return $this->hasOne(PensionDeduction::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(NetPensionClone::class)->orderBy('created_at', 'DESC');
    }

    public function pensionerRelation(): BelongsTo
    {
        return $this->belongsTo(PensionerInformation::class, 'pensioner_id');
    }

    public function pensionerBankRelation(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'pensioner_bank_id');
    }

    public function getPensionerAttribute($value)
    {
        return !empty($value) ? json_decode($value) : null;
    }

    public function setPensionerAttribute($value)
    {
        $this->attributes['pensioner'] = !empty($value) ? json_encode($value) : null;
    }

    public function getPensionerBankAttribute($value)
    {
        return !empty($value) ? json_decode($value) : null;
    }

    public function setPensionerBankAttribute($value)
    {
        $this->attributes['pensioner_bank'] = !empty($value) ? json_encode($value) : null;
    }
}
