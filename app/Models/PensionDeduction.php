<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PensionDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'pension_deduction_id',
        'net_pension_id',
        'commutation_amount',
        'income_tax',
        'recovery',
        'other',
        'amount',
        'description',
        'added_by',
        'edited_by',
    ];

    public function history(): HasMany
    {
        return $this->hasMany(PensionDeductionClone::class)->orderBy('created_at', 'DESC');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function monthlyPension(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MonthlyPension::class);
    }

    public function netPension(): BelongsTo
    {
        return $this->belongsTo(NetPension::class)->select('id', 'pensioner_id', 'net_pension');
    }

    public function pensionerDocuments(): HasMany
    {
        return $this->hasMany(\App\Models\PensionerDocuments::class);
    }
}
