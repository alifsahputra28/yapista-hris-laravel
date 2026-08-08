<?php

namespace App\Models;

use App\Services\EmployeeNikProtectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    public const PROFILE_REVIEW_DRAFT = 'draft';

    public const PROFILE_REVIEW_SUBMITTED = 'submitted';

    public const PROFILE_REVIEW_APPROVED = 'approved';

    public const PROFILE_REVIEW_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'institution_id',
        'position_id',
        'employee_number',
        'full_name',
        'email',
        'nik',
        'family_card_number',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'marital_status',
        'nationality',
        'blood_type',
        'phone',
        'whatsapp_number',
        'identity_address',
        'domicile_same_as_identity',
        'address',
        'domicile_province',
        'domicile_city',
        'domicile_district',
        'domicile_village',
        'domicile_postal_code',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'emergency_contact_address',
        'employee_type',
        'employment_status',
        'join_date',
        'photo',
        'verification_status',
        'verification_note',
        'verified_by',
        'verified_at',
    ];

    protected $hidden = [
        'nik',
        'nik_encrypted',
        'nik_lookup',
        'nik_migrated_at',
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
            'nik_migrated_at' => 'datetime',
            'family_card_number' => 'encrypted',
            'domicile_same_as_identity' => 'boolean',
            'join_date' => 'date',
            'verified_at' => 'datetime',
            'profile_submitted_at' => 'datetime',
            'profile_reviewed_at' => 'datetime',
            'profile_rejected_sections' => 'array',
        ];
    }

    protected function nik(): Attribute
    {
        return Attribute::make(
            get: function (?string $legacyNik, array $attributes): ?string {
                $encryptedNik = $attributes['nik_encrypted'] ?? null;

                if (filled($encryptedNik)) {
                    return app(EmployeeNikProtectionService::class)->decrypt($encryptedNik);
                }

                return $legacyNik;
            },
            set: function (?string $nik): array {
                $service = app(EmployeeNikProtectionService::class);
                $normalized = $service->normalize($nik);

                if ($normalized === null) {
                    return [
                        'nik' => null,
                        'nik_encrypted' => null,
                        'nik_lookup' => null,
                        'nik_migrated_at' => null,
                    ];
                }

                return [
                    'nik' => null,
                    'nik_encrypted' => $service->encrypt($normalized),
                    'nik_lookup' => $service->lookup($normalized),
                    'nik_migrated_at' => now(),
                ];
            },
        );
    }

    protected function maskedNik(): Attribute
    {
        return Attribute::get(
            fn (): ?string => app(EmployeeNikProtectionService::class)->mask($this->nik)
        );
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

    public function profileReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_reviewed_by');
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

    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class)
            ->orderBy('relationship')
            ->orderBy('full_name');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class)
            ->orderByDesc('is_highest')
            ->orderByDesc('graduation_year')
            ->latest();
    }

    public function highestEducation(): HasOne
    {
        return $this->hasOne(EmployeeEducation::class)
            ->where('is_highest', true);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(EmployeeCertification::class)
            ->orderByDesc('is_active')
            ->orderByDesc('issued_at')
            ->orderBy('name');
    }

    public function administrativeDetail(): HasOne
    {
        return $this->hasOne(EmployeeAdministrativeDetail::class);
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

    public function isEligibleForIdCard(): bool
    {
        return $this->isVerified()
            && $this->hasValidEmployeeNumber()
            && ! in_array($this->employment_status, ['nonaktif', 'resign'], true);
    }

    public function canEditProfile(): bool
    {
        return in_array($this->verification_status, ['draft', 'rejected'], true);
    }

    public function canEditProfileCompletion(): bool
    {
        return $this->canEditProfile()
            && in_array($this->profile_review_status, [self::PROFILE_REVIEW_DRAFT, self::PROFILE_REVIEW_REJECTED], true);
    }

    public function isProfileSubmitted(): bool
    {
        return $this->hasProfileReviewStatus(self::PROFILE_REVIEW_SUBMITTED);
    }

    public function hasProfileReviewStatus(string $status): bool
    {
        return $this->profile_review_status === $status;
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
