<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccountClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'pensioner_id',
        'bank_name',
        'branch_name',
        'account_no',
        'ifsc_code',
        'is_active',
        'added_by',
        'edited_by',
    ];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }
}
