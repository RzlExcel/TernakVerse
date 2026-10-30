<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, PasskeyAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'name',
        'email',
        'password',
        'nip_pegawai',
        'nomor_telepon',
        'aktif',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Accessor for name attribute (compatibility with Fortify & UI).
     */
    public function getNameAttribute(): string
    {
        return $this->nama ?? '';
    }

    /**
     * Mutator for name attribute.
     */
    public function setNameAttribute($value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStakeholder(): bool
    {
        return $this->role === 'stakeholder';
    }
}
