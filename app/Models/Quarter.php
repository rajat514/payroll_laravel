<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quarter extends Model
{
    use HasFactory;

    public function employeeQuarter(): HasMany
    {
        return $this->hasMany(EmployeeQuarter::class);
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
