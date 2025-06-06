<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuarterClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'quarter_id',
        'quarter_no',
        'type',
        'license_fee',
        'added_by',
        'edited_by',
    ];

    public function employeeQuarter(): HasMany
    {
        return $this->hasMany(EmployeeQuarter::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }
}
