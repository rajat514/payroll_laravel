<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaySlip extends Model
{
    use HasFactory;

    public function history(): HasMany
    {
        return $this->hasMany(PaySlipClone::class);
    }

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'name', 'role_id');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'name', 'role_id');
    }

    function netSalary(): BelongsTo
    {
        return $this->belongsTo(NetSalary::class, 'net_salary_id', 'id');
    }

    function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
