<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return implode(' ', array_filter([
            $this->prefix,
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]));
    }

    // public function setCodeAttribute($value)
    // {
    //     $this->attributes['employee_code'] = strtoupper($value);
    // }

    public function employeeStatus(): HasMany
    {
        return $this->hasMany(EmployeeStatus::class)->orderBy('created_at', 'DESC');
    }

    public function employeeDesignation(): HasMany
    {
        return $this->hasMany(EmployeeDesignation::class)->orderBy('created_at', 'DESC');
    }

    public function employeeBank(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class)->orderBy('created_at', 'DESC');
    }

    public function employeeQuarter(): HasMany
    {
        return $this->hasMany(EmployeeQuarter::class)->orderBy('created_at', 'DESC');
    }

    public function employeePayStructure(): HasOne
    {
        return $this->hasOne(EmployeePayStructure::class);
    }

    public function crediSocietyMember(): HasMany
    {
        return $this->hasMany(CreditSocietyMembership::class);
    }

    public function employeeGIS(): HasMany
    {
        return $this->hasMany(EmployeeGIS::class);
    }

    public function employeeLoan(): HasMany
    {
        return $this->hasMany(LoanAdvance::class)->orderBy('created_at', 'DESC');
    }

    public function netSalary(): HasMany
    {
        return $this->hasMany(NetSalary::class)->orderBy('created_at', 'DESC');
    }

    function history(): HasMany
    {
        return $this->hasMany(EmployeeClone::class)->orderBy('created_at', 'DESC');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pensioner(): HasOne
    {
        return $this->hasOne(PensionerInformation::class, 'retired_employee_id');
    }

    public function latestEmployeeDesignation()
    {
        return $this->hasOne(EmployeeDesignation::class)
            ->latest('created_at'); // You can use 'created_at' if needed
    }
}
