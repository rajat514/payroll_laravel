<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeductionRecoveryClones extends Model
{
    use HasFactory;

    protected $fillable = [
        'deduction_recovery_id',
        'net_salary_clone_id',
        'deduction_clone_id',
        'deduction_id',
        'type',
        'amount',
        'added_by',
        'edited_by'
    ];
}
