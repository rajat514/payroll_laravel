<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayMatrixLevel extends Model
{
    use HasFactory;

    public function payMatrixCell(): HasMany
    {
        return $this->hasMany(PayMatrixCell::class, 'matrix_level_id');
    }


    public function history(): HasMany
    {
        return $this->hasMany(PayMatrixLevelClone::class);
    }

    public function payCommission(): BelongsTo
    {
        return $this->belongsTo(PayCommission::class)->select('id', 'name', 'year');
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
