<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\Relations\HasOne;

// class NetSalary extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'employee_id',
//         'month',
//         'year',
//         'processing_date',
//         'net_amount',
//         'payment_date',
//         'employee_bank_id',
//         'is_verified',
//         'verified_by',
//         'added_by',
//         'edited_by',
//     ];

//     // protected $casts = [
//     //     'employee' => 'array',
//     //     'employee_bank' => 'array',
//     // ];


//     public function history(): HasMany
//     {
//         return $this->hasMany(NetSalaryClone::class)->orderBy('created_at', 'DESC');
//     }

//     function addedBy(): BelongsTo

//     {
//         return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
//     }

//     function editedBy(): BelongsTo
//     {
//         return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
//     }

//     function verifiedBy(): BelongsTo
//     {
//         return $this->belongsTo(User::class, 'verified_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
//     }

//     function paySlip(): HasOne
//     {
//         return $this->hasOne(PaySlip::class);
//     }

//     function employee(): BelongsTo
//     {
//         return $this->belongsTo(Employee::class);
//     }

//     function deduction(): HasOne
//     {
//         return $this->hasOne(Deduction::class);
//     }

//     function employeeBank(): BelongsTo
//     {
//         return $this->belongsTo(EmployeeBankAccount::class);
//     }

//     public function employeeRelation(): BelongsTo
//     {
//         return $this->belongsTo(Employee::class, 'employee_id');
//     }

//     public function employeeBankRelation(): BelongsTo
//     {
//         return $this->belongsTo(EmployeeBankAccount::class, 'employee_bank_id');
//     }

//     // public function getEmployeeAttribute($value)
//     // {
//     //     if (!empty($value)) {
//     //         return json_decode($value, true);
//     //     }
//     //     return $this->employeeRelation;
//     // }

//     public function getEmployeeAttribute($value)
//     {
//         if (!empty($value)) {
//             return json_decode($value); // object instead of array
//         }
//         return $this->employeeRelation;
//     }

//     // save time (encode)
//     public function setEmployeeAttribute($value)
//     {
//         $this->attributes['employee'] = !empty($value) ? json_encode($value) : null;
//     }

//     public function getEmployeeBankAttribute($value)
//     {
//         if (!empty($value)) {
//             return json_decode($value, true);
//         }
//         return $this->employeeBankRelation;
//     }

//     public function setEmployeeBankAttribute($value)
//     {
//         $this->attributes['employee_bank'] = !empty($value) ? json_encode($value) : null;
//     }
// }




namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// class NetSalary extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'employee_id',
//         'month',
//         'year',
//         'processing_date',
//         'net_amount',
//         'payment_date',
//         'employee_bank_id',
//         'is_verified',
//         'verified_by',
//         'added_by',
//         'edited_by',
//         'employee',
//         'employee_bank',
//     ];

//     /* -------------------
//        Relationships
//     ------------------- */
//     public function history(): HasMany
//     {
//         return $this->hasMany(NetSalaryClone::class)->orderBy('created_at', 'DESC');
//     }

//     function addedBy(): BelongsTo
//     {
//         return $this->belongsTo(User::class, 'added_by', 'id')
//             ->select('id', 'first_name', 'middle_name', 'last_name');
//     }

//     function editedBy(): BelongsTo
//     {
//         return $this->belongsTo(User::class, 'edited_by', 'id')
//             ->select('id', 'first_name', 'middle_name', 'last_name');
//     }

//     function verifiedBy(): BelongsTo
//     {
//         return $this->belongsTo(User::class, 'verified_by', 'id')
//             ->select('id', 'first_name', 'middle_name', 'last_name');
//     }

//     function paySlip(): HasOne
//     {
//         return $this->hasOne(PaySlip::class);
//     }

//     // function employee(): BelongsTo
//     // {
//     //     return $this->belongsTo(Employee::class);
//     // }

//     // function employeeBank(): BelongsTo
//     // {
//     //     return $this->belongsTo(EmployeeBankAccount::class);
//     // }


//     function deduction(): HasOne
//     {
//         return $this->hasOne(Deduction::class);
//     }

//     function employeeRelation(): BelongsTo
//     {
//         return $this->belongsTo(Employee::class, 'employee_id');
//     }

//     function employeeBankRelation(): BelongsTo
//     {
//         return $this->belongsTo(EmployeeBankAccount::class, 'employee_bank_id');
//     }

//     /* -------------------
//        Accessors & Mutators
//     ------------------- */
//     public function getEmployeeAttribute($value)
//     {
//         if (!empty($value)) {
//             // decode as object
//             return json_decode($value);
//         }
//         // fallback to relation
//         return $this->employeeRelation;
//     }

//     public function setEmployeeAttribute($value)
//     {
//         $this->attributes['employee'] = !empty($value) ? json_encode($value) : null;
//     }

//     public function getEmployeeBankAttribute($value)
//     {
//         if (!empty($value)) {
//             // decode as object
//             return json_decode($value);
//         }
//         // fallback to relation
//         return $this->employeeBankRelation;
//     }

//     public function setEmployeeBankAttribute($value)
//     {
//         $this->attributes['employee_bank'] = !empty($value) ? json_encode($value) : null;
//     }
// }

class NetSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'processing_date',
        'net_amount',
        'payment_date',
        'employee_bank_id',
        'is_verified',
        'verified_by',
        'added_by',
        'edited_by',
        'employee',
        'employee_bank',
        'is_finalize',
        'finalized_date',
        'released_date',
    ];

    /* -------------------
       Relationships
    ------------------- */
    public function history(): HasMany
    {
        return $this->hasMany(NetSalaryClone::class)->orderBy('created_at', 'DESC');
    }

    function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id')
            ->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')
            ->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id')
            ->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function paySlip(): HasOne
    {
        return $this->hasOne(PaySlip::class);
    }

    function deduction(): HasOne
    {
        return $this->hasOne(Deduction::class);
    }

    // Relations with different names (if needed)
    function employeeRelation(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    function employeeBankRelation(): BelongsTo
    {
        return $this->belongsTo(EmployeeBankAccount::class, 'employee_bank_id');
    }

    /* -------------------
       Accessors & Mutators
    ------------------- */
    public function getEmployeeAttribute($value)
    {
        return !empty($value) ? json_decode($value) : null;
    }

    public function setEmployeeAttribute($value)
    {
        $this->attributes['employee'] = !empty($value) ? json_encode($value) : null;
    }

    public function getEmployeeBankAttribute($value)
    {
        return !empty($value) ? json_decode($value) : null;
    }

    public function setEmployeeBankAttribute($value)
    {
        $this->attributes['employee_bank'] = !empty($value) ? json_encode($value) : null;
    }
}
