<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PensionerDocuments extends Model
{
    use HasFactory;


    public function history(): HasMany
    {
        return $this->hasMany(PensionerDocumentClone::class, 'pensioner_document_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name', 'role_id');
    }

    public function monthlyPension()
    {
        return $this->belongsTo(\App\Models\MonthlyPension::class);
    }

    public function pensioner()
    {
        return $this->belongsTo(\App\Models\PensionerInformation::class);
    }
}
