<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DearnesAllowanceRateClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'dearnes_allowance_rate_id',
        'rate_percentage',
        'pwd_rate_percentage',
        'effective_from',
        'effective_till',
        'notification_ref',
        'added_by',
        'edited_by',
    ];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
