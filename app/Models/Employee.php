<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
