<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeQuarter extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function quarter(): BelongsTo
    {
        return $this->belongsTo(Quarter::class);
    }

    function addedby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'name', 'role_id');
    }

    function editedby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'name', 'role_id');
    }

    function history(): HasMany
    {
        return $this->hasMany(EmployeeQuarterClone::class);
    }
}
