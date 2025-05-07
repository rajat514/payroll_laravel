<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayMatrixLevel extends Model
{
    use HasFactory;

    public function payMatrixCell(): HasMany
    {
        return $this->hasMany(PayMatrixCell::class);
    }
}
