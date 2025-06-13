<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PensionRelatedInfo extends Model
{
    use HasFactory;

    public function history(): HasMany
    {
        return $this->hasMany(PensionRelatedInfoClone::class, 'pension_rel_info_id');
    }

    public function arrear(): HasMany
    {
        return $this->hasMany(Arrears::class, 'arrear_id');
    }

    public function pensioner(): BelongsTo
    {
        return $this->belongsTo(PensionerInformation::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }
}
