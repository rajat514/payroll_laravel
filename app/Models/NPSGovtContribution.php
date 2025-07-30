<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NPSGovtContribution extends Model
{
    use HasFactory;

    /**
     * Get the latest rate for a given type and date.
     *
     * @param string $type
     * @param string|null $date (Y-m-d)
     * @return NPSGovtContribution|null
     */
    public static function getLatestRate($type, $date = null)
    {
        $date = $date ?: now()->toDateString();
        return self::where('type', $type)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_till')->orWhere('effective_till', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    public function history()
    {
        return $this->hasMany(NPSGovtContributionClone::class, 'n_p_s_govt_contribution_id')->orderBy('created_at', 'DESC');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }
}
