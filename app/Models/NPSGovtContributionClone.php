<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NPSGovtContributionClone extends Model
{
    use HasFactory;

    protected $table = 'n_p_s_govt_contribution_clone';

    protected $fillable = [
        'n_p_s_govt_contribution_id',
        'rate_percentage',
        'type',
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

    public function npsGovtContribution(): BelongsTo
    {
        return $this->belongsTo(NPSGovtContribution::class, 'n_p_s_govt_contribution_id');
    }
} 