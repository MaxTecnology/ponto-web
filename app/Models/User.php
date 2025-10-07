<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const ROLE_COLABORADOR = 'colaborador';
    public const ROLE_RH_MANAGER = 'rh_manager';
    public const ROLE_ADMIN = 'admin';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'deactivated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'password' => 'hashed',
            'deactivated_at' => 'datetime',
        ];
    }

    public function punches(): HasMany
    {
        return $this->hasMany(Punch::class);
    }

    public function adjustRequests(): HasMany
    {
        return $this->hasMany(AdjustRequest::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AdjustRequest::class, 'approver_id');
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }
}
