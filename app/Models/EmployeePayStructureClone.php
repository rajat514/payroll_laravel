<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayStructureClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_pay_structure_id',
        'employee_id',
        'matrix_cell_id',
        'commission',
        'effective_from',
        'effective_till',
        'order_reference',
        'added_by',
        'edited_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function PayMatrixCell(): BelongsTo
    {
        return $this->belongsTo(PayMatrixCell::class, 'matrix_cell_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }
}
