<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'date_of_joining',
        'date_of_retirement',
        'pwd_status',
        'pension_scheme',
        'institute',
        'pension_number',
        'gis_eligibility',
        'gis_no',
        'credit_society_member',
        'email',
        'pancard',
        'increment_month',
        'uniform_allowance_eligibility',
        'hra_eligibility',
        'npa_eligibility',
        'middle_name',
        'prefix',
        'employee_code',
        'user_id',
        'added_by',
        'edited_by',
    ];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }
}
