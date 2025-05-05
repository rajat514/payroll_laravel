<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'status',
        'effective_from',
        'effective_till',
        'remarks',
        'order_reference'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
