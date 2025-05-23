<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PensionerInformationClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'pensioner_information_id',
        'ppo_no',
        'name',
        'type_of_pension',
        'retired_employee_id',
        'pensionerretired_employee_idinformation_id',
        'relation',
        'dob',
        'doj',
        'dor',
        'end_date',
        'status',
        'pan_number',
        'pay_level',
        'pay_commission',
        'equivalent_level',
        'address',
        'city',
        'state',
        'pin_code',
        'mobile_no',
        'email',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'retired_employee_id')->select('id', 'first_name', 'last_name', 'date_of_birth', 'date_of_joining', 'date_of_retirement');
    }

    public function bankAccount(): HasOne
    {
        return $this->hasOne(\App\Models\BankAccount::class);
    }
}
