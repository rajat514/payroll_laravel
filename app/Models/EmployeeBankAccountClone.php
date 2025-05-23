<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBankAccountClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_bank_account_id',
        'employee_id',
        'bank_name',
        'branch_name',
        'account_number',
        'ifsc_code',
        'effective_from',
        'is_active',
        'added_by',
        'edited_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
