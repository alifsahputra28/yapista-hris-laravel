<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    public const TARGET_TYPES = [
        'all' => 'Semua Pegawai Terverifikasi',
        'institution' => 'Berdasarkan Unit Kerja',
        'position' => 'Berdasarkan Jabatan',
        'selected' => 'Pilih Pegawai Manual',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Aktif',
        'closed' => 'Ditutup',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'name',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'description',
        'target_type',
        'created_by',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function participantEmployees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'event_participants')
            ->withPivot('participant_status')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function attendedEmployees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'event_attendances')
            ->withPivot('attendance_status', 'scan_method', 'scanned_at', 'scanned_by')
            ->withTimestamps();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    public function canGenerateParticipants(): bool
    {
        return $this->isDraft();
    }

    public function canScanAttendance(): bool
    {
        return $this->isActive();
    }

    public function getTargetTypeLabelAttribute(): string
    {
        return self::TARGET_TYPES[$this->target_type] ?? $this->target_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
