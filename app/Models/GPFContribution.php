<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GPFContribution extends Model
{
    use HasFactory;

    protected $table = 'g_p_f_contribution';

    protected $fillable = [
        'rate_percentage',
        'effective_from',
        'effective_till',
        'added_by',
        'edited_by',
    ];


    /**
     * Get the latest rate for a given date.
     *
     * @param string|null $date (Y-m-d)
     * @return GPFContribution|null
     */
    public static function getLatestRate($date = null)
    {
        $date = $date ?: now()->toDateString();
        return self::where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_till')->orWhere('effective_till', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    public function history(): HasMany
    {
        return $this->hasMany(GPFContributionClone::class, 'g_p_f_contribution_id')->orderBy('created_at', 'DESC');
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
