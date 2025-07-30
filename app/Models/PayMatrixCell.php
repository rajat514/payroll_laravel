<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayMatrixCell extends Model
{
    use HasFactory;

    public function payMatrixLevel(): BelongsTo
    {
        return $this->belongsTo(PayMatrixLevel::class, 'matrix_level_id');
    }

    public function payStructure(): HasMany
    {
        return $this->hasMany(EmployeePayStructure::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(PayMatrixCellClone::class)->orderBy('created_at', 'DESC');
    }

    function addedBy(): BelongsTo

    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'first_name', 'middle_name', 'last_name');
    }
}
