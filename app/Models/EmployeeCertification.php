<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCertification extends Model
{
    use HasFactory;

    public const EFFECTIVE_STATUS_LABELS = [
        'active' => 'Aktif',
        'expired' => 'Kedaluwarsa',
        'inactive' => 'Tidak Aktif',
        'no_expiry' => 'Tidak Ada Masa Berlaku',
    ];

    protected $fillable = [
        'name',
        'certificate_number',
        'issuer',
        'competency_field',
        'issued_at',
        'expired_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'certificate_number' => 'encrypted',
            'issued_at' => 'date',
            'expired_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getMaskedCertificateNumberAttribute(): string
    {
        if (blank($this->certificate_number)) {
            return 'Belum diisi';
        }

        if (strlen($this->certificate_number) <= 4) {
            return str_repeat('*', strlen($this->certificate_number));
        }

        return str_repeat('*', strlen($this->certificate_number) - 4).substr($this->certificate_number, -4);
    }

    public function getEffectiveStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->expired_at?->isBefore(today())) {
            return 'expired';
        }

        return $this->expired_at ? 'active' : 'no_expiry';
    }

    public function getEffectiveStatusLabelAttribute(): string
    {
        return self::EFFECTIVE_STATUS_LABELS[$this->effective_status];
    }
}
