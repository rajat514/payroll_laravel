<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GPFContributionClone extends Model
{
    use HasFactory;

    protected $table = 'g_p_f_contribution_clone';

    protected $fillable = [
        'g_p_f_contribution_id',
        'rate_percentage',
        'effective_from',
        'effective_till',
        'added_by',
        'edited_by',
    ];


    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

}
