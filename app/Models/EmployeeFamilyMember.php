<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFamilyMember extends Model
{
    use HasFactory;

    public const RELATIONSHIPS = [
        'spouse' => 'Pasangan',
        'child' => 'Anak',
        'father' => 'Ayah',
        'mother' => 'Ibu',
        'sibling' => 'Saudara',
        'guardian' => 'Wali',
        'other' => 'Lainnya',
    ];

    public const BPJS_STATUSES = [
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'not_registered' => 'Belum Terdaftar',
    ];

    protected $fillable = [
        'full_name',
        'relationship',
        'nik',
        'birth_place',
        'birth_date',
        'gender',
        'occupation',
        'is_dependent',
        'bpjs_status',
    ];

    protected $hidden = [
        'nik',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'birth_date' => 'date',
            'is_dependent' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getRelationshipLabelAttribute(): string
    {
        return self::RELATIONSHIPS[$this->relationship] ?? $this->relationship;
    }

    public function getBpjsStatusLabelAttribute(): string
    {
        return self::BPJS_STATUSES[$this->bpjs_status] ?? 'Belum diisi';
    }

    public function getMaskedNikAttribute(): string
    {
        if (blank($this->nik)) {
            return 'Belum diisi';
        }

        return str_repeat('*', max(strlen($this->nik) - 4, 0)).substr($this->nik, -4);
    }
}
