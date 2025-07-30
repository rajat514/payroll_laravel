<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    ];

    public function getSalaryArrearAttribute($value)
    {
        return json_decode($value, true); // returns array
    }

    public function history(): HasMany
    {
        return $this->hasMany(NetSalaryClone::class)->orderBy('created_at', 'DESC');
    }

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function paySlip(): HasOne
    {
        return $this->hasOne(PaySlip::class);
    }

    function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    function deduction(): HasOne
    {
        return $this->hasOne(Deduction::class);
    }

    function employeeBank(): BelongsTo
    {
        return $this->belongsTo(EmployeeBankAccount::class);
    }
}
