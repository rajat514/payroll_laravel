<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NetSalaryClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'net_salary_id',
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

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'name', 'role_id');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'name', 'role_id');
    }

    function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id')->select('id', 'name', 'role_id');
    }
}
