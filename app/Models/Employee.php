<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    public function employeeStatus(): HasMany
    {
        return $this->hasMany(EmployeeStatus::class);
    }

    public function employeeDesignation(): HasMany
    {
        return $this->hasMany(EmployeeDesignation::class);
    }

    public function employeeBank(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    public function employeeQuarter(): HasMany
    {
        return $this->hasMany(EmployeeQuarter::class);
    }

    public function employeePayStructure(): HasMany
    {
        return $this->hasMany(EmployeePayStructure::class);
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
        return $this->hasMany(LoanAdvance::class);
    }

    public function netSalary(): HasMany
    {
        return $this->hasMany(NetSalary::class);
    }

    function history(): HasMany
    {
        return $this->hasMany(EmployeeClone::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'name', 'role_id');
    }
}
