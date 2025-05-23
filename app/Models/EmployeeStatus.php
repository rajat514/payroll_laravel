<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_status_id',
        'employee_id',
        'status',
        'effective_from',
        'effective_till',
        'remarks',
        'order_reference',
        'added_by'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(EmployeeStatusClone::class);
    }

    function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'name', 'role_id');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'name', 'role_id');
    }
}
