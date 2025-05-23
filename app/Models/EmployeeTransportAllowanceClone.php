<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTransportAllowanceClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_ta_id',
        'pay_matrix_level',
        'amount',
        'added_by',
        'edited_by',
    ];

    function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'name', 'role_id');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'name', 'role_id');
    }
}
