<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanAdvance extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(LoanAdvanceClone::class);
    }

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }
}
