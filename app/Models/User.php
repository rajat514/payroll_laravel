<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'otp',
        'otp_expires_at',
        'password_reset_token',
        'password_reset_expires_at',
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // public function setCodeAttribute($value)
    // {
    //     $this->attributes['employee_code'] = strtoupper($value);
    // }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function bankAccount(): HasMany
    {
        return $this->hasMany(\App\Models\BankAccount::class);
    }

    public function pensionerInformation(): HasMany
    {
        return $this->hasMany(\App\Models\PensionerInformation::class);
    }

    public function dearnessRelief(): HasMany
    {
        return $this->hasMany(\App\Models\DearnessRelief::class);
    }

    public function monthlyPension(): HasMany
    {
        return $this->hasMany(\App\Models\MonthlyPension::class);
    }

    public function pensionerDeduction(): HasMany
    {
        return $this->hasMany(\App\Models\PensionDeduction::class);
    }

    public function arrears(): HasMany
    {
        return $this->hasMany(\App\Models\Arrears::class);
    }

    public function pensionerDocument(): HasMany
    {
        return $this->hasMany(\App\Models\PensionerDocuments::class);
    }

    // function isAdmin()
    // {
    //     return $this->roles === 'Admin';
    // }

    function history(): HasMany
    {
        return $this->hasMany(UserClone::class)->orderBy('created_at', 'DESC');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by')->select('id', 'first_name', 'middle_name', 'last_name');
    }
}
