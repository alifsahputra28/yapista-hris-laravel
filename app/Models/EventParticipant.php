<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventParticipant extends Model
{
    use HasFactory;

    public const STATUSES = [
        'invited' => 'Diundang',
        'confirmed' => 'Konfirmasi',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'event_id',
        'employee_id',
        'participant_status',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isInvited(): bool
    {
        return $this->participant_status === 'invited';
    }

    public function isConfirmed(): bool
    {
        return $this->participant_status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->participant_status === 'cancelled';
    }

    public function getParticipantStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->participant_status] ?? $this->participant_status;
    }
}
