<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    function paySlip(): HasMany
    {
        return $this->hasMany(PaySlip::class);
    }

    function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    function deduction(): HasMany
    {
        return $this->hasMany(Deduction::class);
    }
}
