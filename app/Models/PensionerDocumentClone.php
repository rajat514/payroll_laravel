<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PensionerDocumentClone extends Model
{
    use HasFactory;

    protected $fillable = [
        'pensioner_document_id',
        'pensioner_id',
        'document_type',
        'document_number',
        'issue_date',
        'expiry_date',
        'file_path',
        'upload_date',
        'added_by',
        'edited_by',
    ];


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
