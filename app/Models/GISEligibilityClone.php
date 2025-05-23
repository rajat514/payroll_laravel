<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GISEligibilityClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'g_i_s_eligibility_id',
        'pay_matrix_level',
        'scheme_category',
        'amount',
        'added_by',
        'edited_by',
    ];


    function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by', 'id')->select('id', 'name', 'role_id');
    }

    function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by', 'id')->select('id', 'name', 'role_id');
    }
}
