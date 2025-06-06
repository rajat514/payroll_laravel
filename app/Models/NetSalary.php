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

    public function history(): HasMany
    {
        return $this->hasMany(NetSalaryClone::class);
    }

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
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
}
