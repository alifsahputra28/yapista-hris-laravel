<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isHrAdmin(): bool
    {
        return $this->role === 'hr_admin';
    }

    public function isPanitia(): bool
    {
        return $this->role === 'panitia';
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function verifiedEmployees(): HasMany
    {
        return $this->hasMany(Employee::class, 'verified_by');
    }

    public function createdInvitations(): HasMany
    {
        return $this->hasMany(EmployeeInvitation::class, 'created_by');
    }
}
