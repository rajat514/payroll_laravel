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

    // protected $appends = ['name'];

    // public function getNameAttribute()
    // {
    //     return implode(' ', array_filter([
    //         $this->first_name,
    //         $this->middle_name,
    //         $this->last_name,
    //     ]));
    // }

    protected $fillable = [
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
        // Prepare pensioner full name
        $fullName = implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]));

        // If relation is Self, return only the full name
        if ($this->relation === 'Self') {
            return $fullName;
        }

        // Get the retired employee's name
        $employeeName = optional($this->user)->name;

        if ($employeeName) {
            // Build suffix based on relation
            switch ($this->relation) {
                case 'Son':
                    return "{$fullName} S/O {$employeeName}";
                case 'Daughter':
                    return "{$fullName} D/O {$employeeName}";
                case 'Spouse':
                    return "{$fullName} W/O {$employeeName}";
                default:
                    return $fullName;
                    // return "{$fullName} ({$this->relation} of {$employeeName})";
            }
        }

        // If no employee name found, return only the pensioner name
        return $fullName;
    }


    public function history(): HasMany
    {
        return $this->hasMany(PensionerInformationClone::class)->orderBy('created_at', 'DESC');
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
        return $this->belongsTo(Employee::class, 'retired_employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): HasMany
    {
        return $this->hasMany(\App\Models\BankAccount::class, 'pensioner_id')->orderBy('created_at', 'DESC');
    }

    public function pensionRelatedInfo(): HasMany
    {
        return $this->hasMany(\App\Models\PensionRelatedInfo::class, 'pensioner_id');
    }

    public function document(): HasOne
    {
        return $this->hasOne(\App\Models\PensionerDocuments::class, 'pensioner_id');
    }

    public function Arrears(): HasMany
    {
        return $this->hasMany(\App\Models\Arrears::class, 'pensioner_id');
    }
}
