<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PensionerInformation extends Model
{
    use HasFactory;

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]));
    }

    public function history(): HasMany
    {
        return $this->hasMany(PensionerInformationClone::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'retired_employee_id')->select('id', 'first_name', 'middle_name', 'last_name', 'employee_code', 'date_of_birth', 'date_of_joining', 'date_of_retirement');
    }

    public function bankAccount(): HasOne
    {
        return $this->hasOne(\App\Models\BankAccount::class);
    }

    public function Arrears(): HasMany
    {
        return $this->hasMany(\App\Models\Arrears::class, 'pensioner_id');
    }
}
