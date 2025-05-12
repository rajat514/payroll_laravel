<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Arrears extends Model
{
    use HasFactory;

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'name','role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'name', 'role_id');
    }
}
