<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'father_name',
        'region',
        'email',
        'phone',
        'password_hash',
        'user_type',
        'is_active',
        'two_factor_enabled',
        'otp_code',
        'otp_expires_at',
        'email_verified',
        'email_verified_at',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
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
            'otp_expires_at' => 'datetime',
            'google_token_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'email_verified' => 'boolean',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function emailChangeRequests()
    {
        return $this->hasMany(EmailChangeRequest::class);
    }
}
