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

    function addby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    function editby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id');
    }

    function varifyby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'varified_by', 'id');
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
