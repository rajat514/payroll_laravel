<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PensionDeductionClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'pension_deduction_id',
        'pension_id',
        'deduction_type',
        'amount',
        'description',
        'added_by',
        'edited_by',
    ];


    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'name', 'role_id');
    }

    public function monthlyPension(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MonthlyPension::class);
    }
    public function pensionerDocuments(): HasMany
    {
        return $this->hasMany(\App\Models\PensionerDocuments::class);
    }
}
