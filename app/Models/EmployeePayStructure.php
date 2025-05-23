<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePayStructure extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function PayMatrixCell(): BelongsTo
    {
        return $this->belongsTo(PayMatrixCell::class, 'matrix_cell_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(EmployeePayStructureClone::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'name', 'role_id');
    }
}
