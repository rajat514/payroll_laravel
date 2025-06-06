<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

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
}
