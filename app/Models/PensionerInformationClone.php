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
        'first_name',
        'type_of_pension',
        'retired_employee_id',
        'user_id',
        'relation',
        'dob',
        'doj',
        'dor',
        'start_date',
        'end_date',
        'status',
        'pan_number',
        'pay_level',
        'pay_commission',
        'address',
        'city',
        'state',
        'pin_code',
        'mobile_no',
        'email',
        'middle_name',
        'last_name',
        'pay_cell',
        'pay_commission_at_retirement',
        'basic_pay_at_retirement',
        'last_drawn_salary',
        'NPA',
        'HRA',
        'special_pay',
        'added_by',
        'edited_by',
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]));
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'retired_employee_id')->select('id', 'first_name', 'middle_name', 'employee_code', 'last_name', 'date_of_birth', 'date_of_joining', 'date_of_retirement');
    }

    public function bankAccount(): HasOne
    {
        return $this->hasOne(\App\Models\BankAccount::class);
    }
}
