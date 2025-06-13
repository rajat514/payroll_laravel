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

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'middle_name',
        'first_name',
        'last_name',
        'employee_code',
        'email',
        'email_verified_at',
        'institute',
        'is_active',
        'password',
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

    public function role()
    {
        return $this->belongsTo(Role::class)->select('id', 'name');
    }

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

    function isAdmin()
    {
        return $this->role_id === 1;
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
