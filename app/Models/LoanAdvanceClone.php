<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanAdvanceClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_advance_id',
        'employee_id',
        'loan_type',
        'loan_amount',
        'interest_rate',
        'sanctioned_date',
        'total_installments',
        'current_installment',
        'remaining_balance',
        'is_active',
        'added_by',
        'edited_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
