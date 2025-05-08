<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PensionerInformation extends Model
{
    use HasFactory;


    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'retired_employee_id')->select('id', 'first_name', 'last_name', 'date_of_birth', 'date_of_joining', 'date_of_retirement');
    }
}
