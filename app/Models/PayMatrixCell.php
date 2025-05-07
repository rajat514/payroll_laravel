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
}
