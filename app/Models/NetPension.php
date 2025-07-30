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

    public function pensioner(): BelongsTo
    {
        return $this->belongsTo(PensionerInformation::class);
    }

    public function pensionerBank(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
