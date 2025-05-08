<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
