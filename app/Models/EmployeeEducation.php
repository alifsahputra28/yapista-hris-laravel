<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeEducation extends Model
{
    use HasFactory;

    protected $table = 'employee_educations';

    public const EDUCATION_LEVELS = [
        'sd' => 'SD/Sederajat',
        'smp' => 'SMP/Sederajat',
        'sma_smk' => 'SMA/SMK/Sederajat',
        'diploma_1' => 'Diploma I',
        'diploma_2' => 'Diploma II',
        'diploma_3' => 'Diploma III',
        'diploma_4' => 'Diploma IV/Sarjana Terapan',
        'sarjana' => 'Sarjana/S1',
        'profesi' => 'Pendidikan Profesi',
        'magister' => 'Magister/S2',
        'doktor' => 'Doktor/S3',
        'other' => 'Lainnya',
    ];

    protected $fillable = [
        'education_level',
        'institution_name',
        'major',
        'start_year',
        'graduation_year',
        'certificate_number',
        'degree_prefix',
        'degree_suffix',
        'is_highest',
    ];

    protected $hidden = [
        'certificate_number',
    ];

    protected function casts(): array
    {
        return [
            'certificate_number' => 'encrypted',
            'is_highest' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function getEducationLevelLabelAttribute(): string
    {
        return self::EDUCATION_LEVELS[$this->education_level] ?? $this->education_level;
    }

    public function getMaskedCertificateNumberAttribute(): string
    {
        return $this->maskSensitiveNumber($this->certificate_number);
    }

    private function maskSensitiveNumber(?string $value): string
    {
        if (blank($value)) {
            return 'Belum diisi';
        }

        if (strlen($value) <= 4) {
            return str_repeat('*', strlen($value));
        }

        return str_repeat('*', strlen($value) - 4).substr($value, -4);
    }
}
