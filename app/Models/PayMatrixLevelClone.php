<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayMatrixLevelClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_matrix_level_id',
        'name',
        'description',
        'added_by',
        'edited_by'
    ];

    public function payMatrixCell(): HasMany
    {
        return $this->hasMany(PayMatrixCell::class, 'matrix_level_id');
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
