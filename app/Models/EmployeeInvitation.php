<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'invitation_code',
        'email',
        'phone',
        'status',
        'expired_at',
        'used_at',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isUnused(): bool
    {
        return $this->status === 'unused';
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->isUnused() && $this->expired_at !== null && $this->expired_at->isPast());
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isValid(): bool
    {
        return $this->isUnused()
            && ($this->expired_at === null || $this->expired_at->isFuture());
    }
}
