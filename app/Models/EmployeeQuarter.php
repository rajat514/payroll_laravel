<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    function addby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id');
    }

    function editby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id');
    }
}
