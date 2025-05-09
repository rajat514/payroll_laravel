<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PensionDeduction extends Model
{
    use HasFactory;

    public function monthlyPension(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MonthlyPension::class);
    }
}
