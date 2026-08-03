<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    public const EMPLOYEE_NUMBER_LENGTH = 10;

    protected $fillable = [
        'user_id',
        'institution_id',
        'position_id',
        'employee_number',
        'full_name',
        'email',
        'nik',
        'gender',
        'birth_place',
        'birth_date',
        'phone',
        'address',
        'employee_type',
        'employment_status',
        'join_date',
        'photo',
        'verification_status',
        'verification_note',
        'verified_by',
        'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(EmployeeInvitation::class);
    }

    public function activeInvitation(): HasOne
    {
        return $this->hasOne(EmployeeInvitation::class)
            ->where('status', 'unused')
            ->latestOfMany();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function ktpDocument(): HasOne
    {
        return $this->hasOne(EmployeeDocument::class)
            ->where('document_type', 'ktp');
    }

    public function eventParticipants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function qrTokens(): HasMany
    {
        return $this->hasMany(EmployeeQrToken::class);
    }

    public function activeQrToken(): HasOne
    {
        return $this->hasOne(EmployeeQrToken::class)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->latestOfMany();
    }

    public function eventAttendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_participants')
            ->withPivot('participant_status')
            ->withTimestamps();
    }

    public function scopeEligibleForEvents(Builder $query): Builder
    {
        return $query
            ->where('verification_status', 'verified')
            ->whereNotIn('employment_status', ['nonaktif', 'resign']);
    }

    public function isDraft(): bool
    {
        return $this->verification_status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->verification_status === 'submitted';
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    public function isActiveEmployee(): bool
    {
        return $this->employment_status === 'aktif';
    }

    public function getFormattedEmployeeNumberAttribute(): string
    {
        return $this->employee_number ?: 'Belum diisi';
    }

    public function hasValidEmployeeNumber(): bool
    {
        return is_string($this->employee_number)
            && strlen($this->employee_number) === self::EMPLOYEE_NUMBER_LENGTH
            && ctype_digit($this->employee_number);
    }

    public function canEditProfile(): bool
    {
        return in_array($this->verification_status, ['draft', 'rejected'], true);
    }

    public function hasRequiredProfileData(): bool
    {
        return filled($this->full_name)
            && filled($this->nik)
            && filled($this->phone)
            && filled($this->address)
            && filled($this->photo);
    }

    public function hasRequiredDocuments(): bool
    {
        if ($this->relationLoaded('documents')) {
            return $this->documents->contains('document_type', 'ktp');
        }

        return $this->documents()
            ->where('document_type', 'ktp')
            ->exists();
    }
}
