<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdministrativeDetail extends Model
{
    use HasFactory;

    public const TAX_STATUSES = [
        'registered' => 'Terdaftar',
        'not_registered' => 'Belum Terdaftar',
        'not_applicable' => 'Tidak Berlaku',
    ];

    public const BPJS_STATUSES = [
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'not_registered' => 'Belum Terdaftar',
    ];

    public const PTKP_STATUSES = [
        'TK/0', 'TK/1', 'TK/2', 'TK/3',
        'K/0', 'K/1', 'K/2', 'K/3',
        'K/I/0', 'K/I/1', 'K/I/2', 'K/I/3',
    ];

    protected $fillable = [
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'tax_status',
        'tax_identification_number',
        'nik_used_as_tax_id',
        'ptkp_status',
        'bpjs_health_status',
        'bpjs_health_number',
        'bpjs_employment_status',
        'bpjs_employment_number',
    ];

    protected $hidden = [
        'bank_account_number',
        'tax_identification_number',
        'bpjs_health_number',
        'bpjs_employment_number',
    ];

    protected function casts(): array
    {
        return [
            'bank_account_number' => 'encrypted',
            'tax_identification_number' => 'encrypted',
            'nik_used_as_tax_id' => 'boolean',
            'bpjs_health_number' => 'encrypted',
            'bpjs_employment_number' => 'encrypted',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getMaskedBankAccountNumberAttribute(): ?string
    {
        return $this->maskSensitiveNumber($this->bank_account_number);
    }

    public function getMaskedTaxIdentificationNumberAttribute(): ?string
    {
        return $this->maskSensitiveNumber($this->tax_identification_number);
    }

    public function getMaskedBpjsHealthNumberAttribute(): ?string
    {
        return $this->maskSensitiveNumber($this->bpjs_health_number);
    }

    public function getMaskedBpjsEmploymentNumberAttribute(): ?string
    {
        return $this->maskSensitiveNumber($this->bpjs_employment_number);
    }

    private function maskSensitiveNumber(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        return str_repeat('*', strlen($value) - 4).substr($value, -4);
    }
}
