<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeQuarterClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_quarter_id',
        'employee_id',
        'quarter_id',
        'date_of_allotment',
        'date_of_occupation',
        'date_of_leaving',
        'is_current',
        'is_occupied',
        'order_reference',
        'added_by',
        'edited_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function quarter(): BelongsTo
    {
        return $this->belongsTo(Quarter::class);
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
